<?php
// ============================================================
// api/cart.php — Cart AJAX Endpoint
// ============================================================
require_once '../config/db.php';
require_once '../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$action    = $data['action']     ?? '';
$listingId = (int)($data['listing_id'] ?? 0);
$userId    = $_SESSION['user_id'];
$pdo       = getPDO();

if (!$listingId) {
    echo json_encode(['success' => false, 'message' => 'Invalid listing.']);
    exit;
}

if ($action === 'increase') {
    // Check stock
    $listing = $pdo->prepare("SELECT stock FROM listings WHERE listing_id = ? AND status = 'active'");
    $listing->execute([$listingId]);
    $listing = $listing->fetch();

    if (!$listing) {
        echo json_encode(['success' => false, 'message' => 'Listing unavailable.']);
        exit;
    }

    $cart = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND listing_id = ?");
    $cart->execute([$userId, $listingId]);
    $cartRow = $cart->fetch();
    $currentQty = $cartRow ? $cartRow['quantity'] : 0;

    if ($currentQty >= $listing['stock']) {
        echo json_encode(['success' => false, 'message' => 'Maximum stock reached.']);
        exit;
    }

    $pdo->prepare("
        INSERT INTO cart (user_id, listing_id, quantity) VALUES (?,?,1)
        ON DUPLICATE KEY UPDATE quantity = quantity + 1
    ")->execute([$userId, $listingId]);

} elseif ($action === 'decrease') {
    $cart = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND listing_id = ?");
    $cart->execute([$userId, $listingId]);
    $cartRow = $cart->fetch();

    if ($cartRow && $cartRow['quantity'] > 1) {
        $pdo->prepare("UPDATE cart SET quantity = quantity - 1 WHERE user_id = ? AND listing_id = ?")->execute([$userId, $listingId]);
    } else {
        $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND listing_id = ?")->execute([$userId, $listingId]);
    }

} elseif ($action === 'remove') {
    $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND listing_id = ?")->execute([$userId, $listingId]);
}

// Get new quantity & subtotal
$cartRow = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND listing_id = ?");
$cartRow->execute([$userId, $listingId]);
$cartRow = $cartRow->fetch();
$newQty  = $cartRow ? $cartRow['quantity'] : 0;

$price = $pdo->prepare("SELECT price FROM listings WHERE listing_id = ?");
$price->execute([$listingId]);
$price = (float)($price->fetchColumn() ?? 0);

// Cart total
$totalStmt = $pdo->prepare("
    SELECT COALESCE(SUM(c.quantity * l.price), 0)
    FROM cart c JOIN listings l ON c.listing_id = l.listing_id
    WHERE c.user_id = ?
");
$totalStmt->execute([$userId]);
$cartTotal = (float)$totalStmt->fetchColumn();

// Cart count
$countStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
$countStmt->execute([$userId]);
$cartCount = (int)$countStmt->fetchColumn();

echo json_encode([
    'success'    => true,
    'listing_id' => $listingId,
    'new_qty'    => $newQty,
    'subtotal'   => $price * $newQty,
    'cart_total' => $cartTotal,
    'cart_count' => $cartCount,
]);

<?php
// ============================================================
// api/wishlist.php — Wishlist Toggle AJAX Endpoint
// ============================================================
require_once '../config/db.php';
require_once '../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'redirect' => '/login.php']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$listingId = (int)($data['listing_id'] ?? 0);
$userId    = $_SESSION['user_id'];
$pdo       = getPDO();

if (!$listingId) {
    echo json_encode(['success' => false, 'message' => 'Invalid listing.']);
    exit;
}

// Check if already wishlisted
$stmt = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND listing_id = ?");
$stmt->execute([$userId, $listingId]);
$exists = $stmt->fetch();

if ($exists) {
    $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND listing_id = ?")->execute([$userId, $listingId]);
    echo json_encode(['success' => true, 'wishlisted' => false, 'message' => 'Removed from wishlist.']);
} else {
    $pdo->prepare("INSERT INTO wishlist (user_id, listing_id) VALUES (?,?)")->execute([$userId, $listingId]);
    echo json_encode(['success' => true, 'wishlisted' => true, 'message' => 'Added to wishlist!']);
}

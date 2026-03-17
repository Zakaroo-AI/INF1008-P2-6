<?php
// ============================================================
// order-detail.php — Order Detail with Status Timeline
// ============================================================
require_once 'includes/header.php';
requireLogin();

$pdo     = getPDO();
$orderId = (int)($_GET['id'] ?? 0);

// Fetch order — ensure it belongs to current user (security check)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND buyer_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Order not found.'];
    header('Location: /orders.php'); exit;
}

$pageTitle = 'Order #' . str_pad($orderId, 4, '0', STR_PAD_LEFT);

// Fetch order items
$stmt2 = $pdo->prepare("
    SELECT oi.*, l.title, c.card_name, c.image_url, c.typing
    FROM order_items oi
    JOIN listings l ON oi.listing_id = l.listing_id
    JOIN cards    c ON l.card_id     = c.card_id
    WHERE oi.order_id = ?
");
$stmt2->execute([$orderId]);
$items = $stmt2->fetchAll();

// Status steps for timeline
$steps = [
    ['key' => 'pending',    'label' => 'Order Placed', 'icon' => 'check-circle'],
    ['key' => 'processing', 'label' => 'Processing',   'icon' => 'gear'],
    ['key' => 'shipped',    'label' => 'Shipped',       'icon' => 'truck'],
    ['key' => 'delivered',  'label' => 'Delivered',     'icon' => 'house-check'],
];
$statusOrder = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3];
$currentIdx  = $statusOrder[$order['status']] ?? 0;
?>

<div class="container py-5" style="max-width:800px;">
    <!-- Back button -->
    <a href="/orders.php" class="btn btn-outline-secondary btn-sm mb-4">
        <i class="bi bi-arrow-left me-1"></i>Back to Orders
    </a>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h2 fw-bold mb-0" style="color:var(--pm-blue);">
            Order #<?= str_pad($orderId, 4, '0', STR_PAD_LEFT) ?>
        </h1>
        <span class="text-muted small">Placed on <?= date('d M Y, g:ia', strtotime($order['created_at'])) ?></span>
    </div>

    <!-- Status Timeline -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" aria-label="Order status">
        <h2 class="h6 fw-bold mb-4">Delivery Status</h2>
        <div class="status-timeline" role="list">
            <?php foreach ($steps as $idx => $step):
                $isDone   = $idx < $currentIdx;
                $isActive = $idx === $currentIdx;
                $cls = $isDone ? 'done' : ($isActive ? 'active' : '');
            ?>
            <div class="status-step <?= $cls ?>" role="listitem"
                 aria-current="<?= $isActive ? 'step' : 'false' ?>">
                <div class="step-icon">
                    <i class="bi bi-<?= e($step['icon']) ?>"></i>
                </div>
                <div class="step-label"><?= e($step['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Order Items -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h2 class="h6 fw-bold mb-3">Items Ordered</h2>
        <?php foreach ($items as $item): ?>
        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
            <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['card_name']) ?>"
                 style="width:64px;height:64px;object-fit:contain;background:#eef0ff;border-radius:12px;padding:6px;">
            <div class="flex-grow-1">
                <p class="mb-0 fw-bold"><?= e($item['title']) ?></p>
                <span class="type-badge" style="background:<?= typeBadgeColor($item['typing']) ?>; font-size:0.7rem;">
                    <?= e($item['typing']) ?>
                </span>
                <p class="mb-0 text-muted small mt-1">Qty: <?= $item['quantity'] ?></p>
            </div>
            <div class="text-end">
                <p class="mb-0 fw-bold text-primary">$<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></p>
                <p class="mb-0 text-muted small">$<?= number_format($item['unit_price'], 2) ?> each</p>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Total -->
        <div class="d-flex justify-content-between align-items-center pt-2">
            <span class="fw-bold fs-5">Order Total</span>
            <span class="fw-bold fs-5 text-primary">$<?= number_format($order['total_price'], 2) ?></span>
        </div>
    </div>

    <a href="/browse.php" class="btn btn-pm-primary">
        <i class="bi bi-search me-2"></i>Continue Shopping
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>

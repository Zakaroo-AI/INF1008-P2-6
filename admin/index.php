<?php
// ============================================================
// admin/index.php — Admin Dashboard
// ============================================================
require_once '../includes/header.php';
$pageTitle = 'Admin Dashboard';
requireAdmin();
$pdo = getPDO();

$stats = [
    'users'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role='trainer'")->fetchColumn(),
    'listings' => $pdo->query("SELECT COUNT(*) FROM listings WHERE status='active'")->fetchColumn(),
    'orders'   => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue'  => $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders")->fetchColumn(),
];

// Recent orders
$recentOrders = $pdo->query("
    SELECT o.order_id, o.total_price, o.status, o.created_at, u.username
    FROM orders o JOIN users u ON o.buyer_id = u.user_id
    ORDER BY o.created_at DESC LIMIT 5
")->fetchAll();

// Recent listings
$recentListings = $pdo->query("
    SELECT l.listing_id, l.title, l.price, l.status, u.username AS seller, p.name AS pokemon_name
    FROM listings l
    JOIN users   u ON l.seller_id  = u.user_id
    JOIN pokemon p ON l.pokemon_id = p.pokemon_id
    ORDER BY l.created_at DESC LIMIT 5
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 px-4 py-4">
            <h1 class="h3 fw-bold mb-4">Dashboard</h1>

            <!-- Stat Cards -->
            <div class="row g-4 mb-5">
                <?php
                $cards = [
                    ['label'=>'Trainers',       'value'=>$stats['users'],    'icon'=>'people',        'color'=>'primary'],
                    ['label'=>'Active Listings', 'value'=>$stats['listings'], 'icon'=>'tags',          'color'=>'success'],
                    ['label'=>'Total Orders',    'value'=>$stats['orders'],   'icon'=>'bag',           'color'=>'warning'],
                    ['label'=>'Total Revenue',   'value'=>'$'.number_format($stats['revenue'],2), 'icon'=>'cash-stack','color'=>'danger'],
                ];
                foreach ($cards as $card): ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card border-0 p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small"><?= $card['label'] ?></div>
                                <div class="fs-3 fw-bold mt-1 text-<?= $card['color'] ?>"><?= $card['value'] ?></div>
                            </div>
                            <div class="fs-1 text-<?= $card['color'] ?> opacity-25">
                                <i class="bi bi-<?= $card['icon'] ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-4">
                <!-- Recent Orders -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-dark text-white fw-bold py-3">Recent Orders</div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 small">
                                <thead class="table-light">
                                    <tr><th>Order</th><th>Trainer</th><th>Total</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $o): ?>
                                    <tr>
                                        <td><a href="/admin/orders.php">#<?= str_pad($o['order_id'],4,'0',STR_PAD_LEFT) ?></a></td>
                                        <td><?= e($o['username']) ?></td>
                                        <td class="fw-bold text-primary">$<?= number_format($o['total_price'],2) ?></td>
                                        <td><span class="badge bg-secondary"><?= e($o['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-end"><a href="/admin/orders.php" class="btn btn-sm btn-outline-primary">View All</a></div>
                    </div>
                </div>

                <!-- Recent Listings -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-dark text-white fw-bold py-3">Recent Listings</div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 small">
                                <thead class="table-light">
                                    <tr><th>Pokémon</th><th>Seller</th><th>Price</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentListings as $l): ?>
                                    <tr>
                                        <td><?= e($l['pokemon_name']) ?></td>
                                        <td><?= e($l['seller']) ?></td>
                                        <td class="fw-bold text-primary">$<?= number_format($l['price'],2) ?></td>
                                        <td><span class="badge bg-<?= $l['status']==='active'?'success':'secondary' ?>"><?= e($l['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-end"><a href="/admin/listings.php" class="btn btn-sm btn-outline-primary">View All</a></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

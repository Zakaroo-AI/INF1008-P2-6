<?php
// ============================================================
// listing.php — Single Listing Detail Page
// ============================================================
require_once 'includes/header.php';
$pdo = getPDO();

$id      = (int)($_GET['id'] ?? 0);
$stmt    = $pdo->prepare("
    SELECT l.*, p.*, p.name AS pokemon_name, u.username AS seller_name, u.user_id AS seller_user_id
    FROM listings l
    JOIN pokemon p ON l.pokemon_id = p.pokemon_id
    JOIN users   u ON l.seller_id  = u.user_id
    WHERE l.listing_id = ?
");
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Listing not found.'];
    header('Location: /browse.php'); exit;
}

$pageTitle = $listing['title'];

// Handle Add to Cart POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); exit;
    }
    if ($listing['status'] !== 'active' || $listing['stock'] < 1) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'This listing is no longer available.'];
        header('Location: /listing.php?id=' . $id); exit;
    }
    // Upsert into cart
    $stmt2 = $pdo->prepare("
        INSERT INTO cart (user_id, listing_id, quantity)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + 1, ?)
    ");
    $stmt2->execute([$_SESSION['user_id'], $id, $listing['stock']]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => $listing['pokemon_name'] . ' added to your cart!'];
    header('Location: /cart.php'); exit;
}

// Related listings (same type)
$related = $pdo->prepare("
    SELECT l.listing_id, l.title, l.price, p.name AS pokemon_name, p.image_url, p.type_primary
    FROM listings l JOIN pokemon p ON l.pokemon_id = p.pokemon_id
    WHERE (p.type_primary = ? OR p.type_secondary = ?)
      AND l.listing_id <> ? AND l.status = 'active'
    LIMIT 4
");
$related->execute([$listing['type_primary'], $listing['type_primary'], $id]);
$relatedListings = $related->fetchAll();
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/browse.php">Browse</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($listing['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Left: Image -->
        <div class="col-md-5 text-center">
            <div class="bg-light rounded-4 p-4 mb-3" style="background:linear-gradient(135deg,#e8ecff,#f0f4ff)!important;">
                <img src="<?= e($listing['image_url']) ?>"
                     alt="<?= e($listing['pokemon_name']) ?>"
                     class="img-fluid" style="max-height:320px;">
            </div>
            <!-- Wishlist -->
            <?php if (isLoggedIn()): ?>
            <button class="btn btn-outline-danger wishlist-btn w-100"
                    data-listing="<?= $listing['listing_id'] ?>"
                    aria-pressed="false" aria-label="Add to wishlist">
                <i class="bi bi-heart me-2"></i>Add to Wishlist
            </button>
            <?php endif; ?>
        </div>

        <!-- Right: Details -->
        <div class="col-md-7">
            <!-- Types -->
            <div class="mb-2">
                <span class="type-badge" style="background:<?= typeBadgeColor($listing['type_primary']) ?>">
                    <?= e($listing['type_primary']) ?>
                </span>
                <?php if ($listing['type_secondary']): ?>
                <span class="type-badge ms-1" style="background:<?= typeBadgeColor($listing['type_secondary']) ?>">
                    <?= e($listing['type_secondary']) ?>
                </span>
                <?php endif; ?>
                <span class="badge ms-2 rarity-<?= strtolower(e($listing['rarity'])) ?>">
                    <?= e($listing['rarity']) ?>
                </span>
            </div>

            <h1 class="h2 fw-bold"><?= e($listing['title']) ?></h1>
            <p class="text-muted">Listed by <strong><?= e($listing['seller_name']) ?></strong></p>

            <p class="fs-2 fw-bold text-primary mb-1">$<?= number_format($listing['price'], 2) ?></p>
            <p class="text-muted small mb-3">
                <?php if ($listing['stock'] > 0): ?>
                    <i class="bi bi-check-circle text-success me-1"></i><?= $listing['stock'] ?> available
                <?php else: ?>
                    <i class="bi bi-x-circle text-danger me-1"></i>Out of stock
                <?php endif; ?>
            </p>

            <!-- Add to Cart -->
            <?php if ($listing['status'] === 'active' && $listing['stock'] > 0): ?>
            <form method="POST">
                <button type="submit" name="add_to_cart" class="btn btn-pm-primary btn-lg px-5 mb-3">
                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                </button>
            </form>
            <?php else: ?>
            <button class="btn btn-secondary btn-lg px-5 mb-3" disabled>Unavailable</button>
            <?php endif; ?>

            <hr>
            <h2 class="h6 fw-bold mb-2">Description</h2>
            <p><?= nl2br(e($listing['description'] ?? $listing['p.description'] ?? 'No description provided.')) ?></p>

            <!-- Pokémon Stats -->
            <h2 class="h6 fw-bold mt-4 mb-3">Base Stats</h2>
            <?php
            $stats = [
                'HP'      => ['value' => $listing['hp'],      'color' => '#FF5959'],
                'Attack'  => ['value' => $listing['attack'],  'color' => '#F5AC78'],
                'Defense' => ['value' => $listing['defense'], 'color' => '#FAE078'],
                'Speed'   => ['value' => $listing['speed'],   'color' => '#FA92B2'],
            ];
            foreach ($stats as $name => $stat): ?>
            <div class="stat-bar-wrap d-flex align-items-center gap-2">
                <span class="stat-label"><?= $name ?></span>
                <span class="fw-bold" style="min-width:30px;"><?= $stat['value'] ?></span>
                <div class="flex-grow-1 bg-light rounded" style="height:10px;">
                    <div class="stat-bar rounded"
                         style="background:<?= $stat['color'] ?>; height:10px;"
                         data-value="<?= $stat['value'] ?>" data-max="200"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Related Listings -->
    <?php if (!empty($relatedListings)): ?>
    <section class="mt-5" aria-label="Related listings">
        <h2 class="section-title">Related Listings</h2>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php foreach ($relatedListings as $rel): ?>
            <div class="col">
                <a href="/listing.php?id=<?= $rel['listing_id'] ?>" class="text-decoration-none">
                    <div class="card listing-card h-100 text-center p-2">
                        <img src="<?= e($rel['image_url']) ?>" alt="<?= e($rel['pokemon_name']) ?>"
                             class="card-img-top" style="height:100px; object-fit:contain; padding:8px;">
                        <div class="card-body p-2">
                            <p class="small fw-bold mb-0 text-dark"><?= e($rel['pokemon_name']) ?></p>
                            <p class="small text-primary fw-bold mb-0">$<?= number_format($rel['price'], 2) ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

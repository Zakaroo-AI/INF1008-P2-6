<?php
// ============================================================
// browse.php — Browse All Listings
// ============================================================
require_once 'includes/header.php';
$pageTitle = 'Browse Listings';
$pdo = getPDO();

// --- Filters from GET ---
$search  = trim($_GET['q']      ?? '');
$type    = trim($_GET['type']   ?? '');
$rarity  = trim($_GET['rarity'] ?? '');
$sort    = trim($_GET['sort']   ?? 'newest');
$minPrice = (float)($_GET['min'] ?? 0);
$maxPrice = (float)($_GET['max'] ?? 99999);

// --- Build query ---
$where  = ["l.status = 'active'"];
$params = [];

if ($search) {
    $where[]  = "(p.name LIKE ? OR l.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($type) {
    $where[]  = "(p.type_primary = ? OR p.type_secondary = ?)";
    $params[] = $type;
    $params[] = $type;
}
if ($rarity) {
    $where[]  = "p.rarity = ?";
    $params[] = $rarity;
}
$where[]  = "l.price >= ?";
$params[] = $minPrice;
$where[]  = "l.price <= ?";
$params[] = $maxPrice;

$orderBy = match($sort) {
    'price_asc'  => 'l.price ASC',
    'price_desc' => 'l.price DESC',
    'name_asc'   => 'p.name ASC',
    default      => 'l.created_at DESC'
};

$sql = "
    SELECT l.*, p.name AS pokemon_name, p.type_primary, p.type_secondary,
           p.rarity, p.image_url, u.username AS seller_name
    FROM listings l
    JOIN pokemon p ON l.pokemon_id = p.pokemon_id
    JOIN users   u ON l.seller_id  = u.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY $orderBy
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$types    = ['Fire','Water','Grass','Electric','Psychic','Ghost','Dragon','Dark','Fighting','Normal','Fairy','Rock','Steel','Poison','Flying','Rock','Ground','Ice','Bug'];
$rarities = ['Common','Rare','Epic','Legendary'];
?>

<div class="container py-5">
    <div class="row g-4">

        <!-- Sidebar Filters -->
        <aside class="col-lg-3" aria-label="Listing filters">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top:80px;">
                <h2 class="h6 fw-bold mb-3" style="color:var(--pm-blue);">
                    <i class="bi bi-funnel me-2"></i>Filter Listings
                </h2>
                <form method="GET" id="filterForm">
                    <?php if ($search): ?>
                    <input type="hidden" name="q" value="<?= e($search) ?>">
                    <?php endif; ?>

                    <!-- Type -->
                    <label for="filterType" class="form-label small fw-semibold">Pokémon Type</label>
                    <select class="form-select form-select-sm mb-3" name="type" id="filterType" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <?php foreach (array_unique($types) as $t): ?>
                        <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Rarity -->
                    <label for="filterRarity" class="form-label small fw-semibold">Rarity</label>
                    <select class="form-select form-select-sm mb-3" name="rarity" id="filterRarity" onchange="this.form.submit()">
                        <option value="">All Rarities</option>
                        <?php foreach ($rarities as $r): ?>
                        <option value="<?= e($r) ?>" <?= $rarity === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Sort -->
                    <label for="filterSort" class="form-label small fw-semibold">Sort By</label>
                    <select class="form-select form-select-sm mb-3" name="sort" id="filterSort" onchange="this.form.submit()">
                        <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest First</option>
                        <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Price: High to Low</option>
                        <option value="name_asc"   <?= $sort==='name_asc'   ? 'selected':'' ?>>Name A–Z</option>
                    </select>

                    <!-- Price Range -->
                    <label class="form-label small fw-semibold">Price Range</label>
                    <div class="d-flex gap-2 mb-3">
                        <input type="number" class="form-control form-control-sm" name="min"
                               placeholder="Min" value="<?= $minPrice > 0 ? $minPrice : '' ?>" min="0">
                        <input type="number" class="form-control form-control-sm" name="max"
                               placeholder="Max" value="<?= $maxPrice < 99999 ? $maxPrice : '' ?>" min="0">
                    </div>
                    <button type="submit" class="btn btn-pm-primary btn-sm w-100">Apply</button>

                    <?php if ($type || $rarity || $search || $minPrice || $maxPrice < 99999): ?>
                    <a href="/browse.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">Clear Filters</a>
                    <?php endif; ?>
                </form>
            </div>
        </aside>

        <!-- Listings Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="h4 fw-bold mb-0">
                    <?php if ($search): ?>Results for "<?= e($search) ?>"
                    <?php elseif ($type): ?><?= e($type) ?> Type Pokémon
                    <?php else: ?>All Listings<?php endif; ?>
                </h1>
                <span class="badge bg-secondary"><?= count($listings) ?> listing<?= count($listings) !== 1 ? 's' : '' ?> found</span>
            </div>

            <?php if (empty($listings)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-search display-1 text-muted"></i>
                    <p class="mt-3 text-muted">No listings found. Try adjusting your filters.</p>
                    <a href="/browse.php" class="btn btn-pm-primary mt-2">Clear Filters</a>
                </div>
            <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php foreach ($listings as $listing): ?>
                <div class="col">
                    <article class="card listing-card h-100" aria-label="<?= e($listing['title']) ?>">
                        <!-- Wishlist button -->
                        <?php if (isLoggedIn()): ?>
                        <button class="btn btn-light btn-sm wishlist-btn position-absolute top-0 end-0 m-2 rounded-circle"
                                data-listing="<?= $listing['listing_id'] ?>"
                                aria-label="Add to wishlist" aria-pressed="false"
                                title="Add to wishlist" style="z-index:2;">
                            <i class="bi bi-heart"></i>
                        </button>
                        <?php endif; ?>

                        <a href="/listing.php?id=<?= $listing['listing_id'] ?>">
                            <img src="<?= e($listing['image_url']) ?>"
                                 class="card-img-top"
                                 alt="<?= e($listing['pokemon_name']) ?>"
                                 loading="lazy">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="type-badge" style="background:<?= typeBadgeColor($listing['type_primary']) ?>">
                                    <?= e($listing['type_primary']) ?>
                                </span>
                                <?php if ($listing['type_secondary']): ?>
                                <span class="type-badge ms-1" style="background:<?= typeBadgeColor($listing['type_secondary']) ?>">
                                    <?= e($listing['type_secondary']) ?>
                                </span>
                                <?php endif; ?>
                                <span class="badge ms-1 rarity-<?= strtolower(e($listing['rarity'])) ?>">
                                    <?= e($listing['rarity']) ?>
                                </span>
                            </div>
                            <h3 class="card-title fs-6 fw-bold">
                                <a href="/listing.php?id=<?= $listing['listing_id'] ?>" class="text-decoration-none text-dark">
                                    <?= e($listing['title']) ?>
                                </a>
                            </h3>
                            <p class="text-muted small">by <?= e($listing['seller_name']) ?></p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <span class="fs-5 fw-bold text-primary">$<?= number_format($listing['price'], 2) ?></span>
                                <a href="/listing.php?id=<?= $listing['listing_id'] ?>"
                                   class="btn btn-pm-primary btn-sm">View</a>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

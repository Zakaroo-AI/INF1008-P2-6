<?php
// ============================================================
// admin/listings.php — Manage All Listings
// ============================================================
require_once '../includes/header.php';
$pageTitle = 'Manage Listings';
requireAdmin();
$pdo = getPDO();

// Handle status update or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $listingId = (int)($_POST['listing_id'] ?? 0);
    $action    = $_POST['action'] ?? '';

    if ($listingId) {
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM listings WHERE listing_id = ?")->execute([$listingId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Listing deleted.'];
        } elseif (in_array($action, ['active','sold','removed'])) {
            $pdo->prepare("UPDATE listings SET status = ? WHERE listing_id = ?")->execute([$action, $listingId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Listing status updated.'];
        }
    }
    header('Location: /admin/listings.php'); exit;
}

$listings = $pdo->query("
    SELECT l.*, p.name AS pokemon_name, p.type_primary, p.image_url, u.username AS seller_name
    FROM listings l
    JOIN pokemon p ON l.pokemon_id = p.pokemon_id
    JOIN users   u ON l.seller_id  = u.user_id
    ORDER BY l.created_at DESC
")->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 px-4 py-4">
            <h1 class="h3 fw-bold mb-4">Manage Listings
                <span class="badge bg-secondary ms-2 fs-6"><?= count($listings) ?></span>
            </h1>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr><th>Pokémon</th><th>Title</th><th>Seller</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listings as $l): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= e($l['image_url']) ?>" alt=""
                                             style="width:40px;height:40px;object-fit:contain;background:#eef0ff;border-radius:6px;">
                                        <span class="fw-bold small"><?= e($l['pokemon_name']) ?></span>
                                    </div>
                                </td>
                                <td class="small"><?= e(mb_strimwidth($l['title'], 0, 40, '...')) ?></td>
                                <td class="small"><?= e($l['seller_name']) ?></td>
                                <td class="fw-bold text-primary">$<?= number_format($l['price'],2) ?></td>
                                <td><?= $l['stock'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $l['status']==='active'?'success':($l['status']==='sold'?'secondary':'danger') ?>">
                                        <?= e($l['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex gap-1 flex-wrap">
                                        <input type="hidden" name="listing_id" value="<?= $l['listing_id'] ?>">
                                        <select name="action" class="form-select form-select-sm" style="width:110px;">
                                            <option value="active"  <?= $l['status']==='active'  ?'selected':'' ?>>Active</option>
                                            <option value="sold"    <?= $l['status']==='sold'    ?'selected':'' ?>>Sold</option>
                                            <option value="removed" <?= $l['status']==='removed' ?'selected':'' ?>>Removed</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-pm-primary">Set</button>
                                        <button type="submit" name="action" value="delete"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Delete this listing permanently?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

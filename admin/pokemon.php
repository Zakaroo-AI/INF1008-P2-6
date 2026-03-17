<?php
// ============================================================
// admin/pokemon.php — Manage Cards Catalogue
// ============================================================
require_once '../includes/header.php';
$pageTitle = 'Manage Cards';
requireAdmin();
$pdo = getPDO();

$errors  = [];
$editing = null;

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM cards WHERE card_id = ?")->execute([(int)$_POST['delete_id']]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Card deleted from catalogue.'];
    header('Location: /admin/pokemon.php'); exit;
}

// EDIT: load existing for form
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE card_id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

// CREATE or UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $cardId      = (int)($_POST['card_id']     ?? 0);
    $cardName    = trim($_POST['card_name']     ?? '');
    $setName     = trim($_POST['set_name']      ?? '');
    $cardNumber  = trim($_POST['card_number']   ?? '');
    $typing      = trim($_POST['typing']        ?? '');
    $rarity      = trim($_POST['rarity']        ?? 'Common');
    $imageUrl    = trim($_POST['image_url']     ?? '');
    $description = trim($_POST['description']   ?? '');

    if (strlen($cardName) < 2)   $errors[] = 'Card name must be at least 2 characters.';
    if (empty($setName))         $errors[] = 'Set name is required.';
    if (empty($cardNumber))      $errors[] = 'Card number is required.';
    if (empty($typing))          $errors[] = 'Typing is required.';
    if (!filter_var($imageUrl, FILTER_VALIDATE_URL) && !empty($imageUrl)) $errors[] = 'Invalid image URL.';

    if (empty($errors)) {
        if ($cardId > 0) {
            $pdo->prepare("UPDATE cards SET card_name=?,set_name=?,card_number=?,typing=?,rarity=?,image_url=?,description=? WHERE card_id=?")
                ->execute([$cardName,$setName,$cardNumber,$typing,$rarity,$imageUrl,$description,$cardId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => $cardName . ' updated!'];
        } else {
            $pdo->prepare("INSERT INTO cards (card_name,set_name,card_number,typing,rarity,image_url,description) VALUES (?,?,?,?,?,?,?)")
                ->execute([$cardName,$setName,$cardNumber,$typing,$rarity,$imageUrl,$description]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => $cardName . ' added to catalogue!'];
        }
        header('Location: /admin/pokemon.php'); exit;
    } else {
        $editing = $_POST;
        $editing['card_id'] = $cardId;
    }
}

$allCards   = $pdo->query("SELECT * FROM cards ORDER BY card_name")->fetchAll();
$typings    = ['Fire','Water','Grass','Lightning','Psychic','Fighting','Darkness','Metal','Dragon','Colorless','Fairy'];
$rarities   = ['Common','Uncommon','Rare','Holo Rare','Ultra Rare','Secret Rare'];
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 px-4 py-4">
            <h1 class="h3 fw-bold mb-4">Cards Catalogue</h1>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <!-- Add / Edit Form -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h2 class="h6 fw-bold mb-3"><?= $editing ? 'Edit Card' : 'Add New Card' ?></h2>
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="card_id" value="<?= $editing ? (int)$editing['card_id'] : 0 ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Card Name *</label>
                            <input type="text" name="card_name" class="form-control" required
                                   value="<?= $editing ? e($editing['card_name']) : '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Set Name *</label>
                            <input type="text" name="set_name" class="form-control" required
                                   placeholder="e.g. Base Set"
                                   value="<?= $editing ? e($editing['set_name']) : '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Card Number *</label>
                            <input type="text" name="card_number" class="form-control" required
                                   placeholder="e.g. 4/102"
                                   value="<?= $editing ? e($editing['card_number']) : '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Typing *</label>
                            <select name="typing" class="form-select" required>
                                <option value="">—</option>
                                <?php foreach ($typings as $t): ?>
                                <option value="<?= $t ?>" <?= ($editing && $editing['typing']===$t)?'selected':'' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rarity</label>
                            <select name="rarity" class="form-select">
                                <?php foreach ($rarities as $r): ?>
                                <option value="<?= $r ?>" <?= ($editing && $editing['rarity']===$r)?'selected':'' ?>><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Image URL</label>
                            <input type="url" name="image_url" class="form-control"
                                   placeholder="https://..."
                                   value="<?= $editing ? e($editing['image_url']) : '' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= $editing ? e($editing['description']) : '' ?></textarea>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-pm-primary"><?= $editing ? 'Save Changes' : 'Add Card' ?></button>
                        <?php if ($editing): ?><a href="/admin/pokemon.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Cards Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr><th>Image</th><th>Card Name</th><th>Set</th><th>Number</th><th>Typing</th><th>Rarity</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allCards as $c): ?>
                            <tr>
                                <td><img src="<?= e($c['image_url']) ?>" alt="" style="width:44px;height:60px;object-fit:contain;background:#eef0ff;border-radius:4px;"></td>
                                <td class="fw-bold"><?= e($c['card_name']) ?></td>
                                <td class="small"><?= e($c['set_name']) ?></td>
                                <td class="small"><?= e($c['card_number']) ?></td>
                                <td><span class="type-badge" style="background:<?= typeBadgeColor($c['typing']) ?>; font-size:0.7rem;"><?= e($c['typing']) ?></span></td>
                                <td><span class="badge rarity-<?= strtolower(str_replace(' ','-',e($c['rarity']))) ?>"><?= e($c['rarity']) ?></span></td>
                                <td>
                                    <a href="/admin/pokemon.php?edit=<?= $c['card_id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete <?= e($c['card_name']) ?>?')">
                                        <input type="hidden" name="delete_id" value="<?= $c['card_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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

<?php
// ============================================================
// admin/pokemon.php — Manage Pokémon Catalogue
// ============================================================
require_once '../includes/header.php';
$pageTitle = 'Manage Pokémon';
requireAdmin();
$pdo = getPDO();

$errors  = [];
$editing = null;

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM pokemon WHERE pokemon_id = ?")->execute([(int)$_POST['delete_id']]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pokémon deleted from catalogue.'];
    header('Location: /admin/pokemon.php'); exit;
}

// EDIT: load existing for form
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM pokemon WHERE pokemon_id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

// CREATE or UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $pokemonId   = (int)($_POST['pokemon_id']    ?? 0);
    $name        = trim($_POST['name']           ?? '');
    $typePri     = trim($_POST['type_primary']   ?? '');
    $typeSec     = trim($_POST['type_secondary'] ?? '') ?: null;
    $hp          = (int)($_POST['hp']            ?? 50);
    $attack      = (int)($_POST['attack']        ?? 50);
    $defense     = (int)($_POST['defense']       ?? 50);
    $speed       = (int)($_POST['speed']         ?? 50);
    $rarity      = trim($_POST['rarity']         ?? 'Common');
    $imageUrl    = trim($_POST['image_url']       ?? '');
    $description = trim($_POST['description']    ?? '');

    if (strlen($name) < 2)    $errors[] = 'Name must be at least 2 characters.';
    if (empty($typePri))      $errors[] = 'Primary type is required.';
    if (!filter_var($imageUrl, FILTER_VALIDATE_URL) && !empty($imageUrl)) $errors[] = 'Invalid image URL.';

    if (empty($errors)) {
        if ($pokemonId > 0) {
            // Update
            $pdo->prepare("UPDATE pokemon SET name=?,type_primary=?,type_secondary=?,hp=?,attack=?,defense=?,speed=?,rarity=?,image_url=?,description=? WHERE pokemon_id=?")
                ->execute([$name,$typePri,$typeSec,$hp,$attack,$defense,$speed,$rarity,$imageUrl,$description,$pokemonId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => $name . ' updated!'];
        } else {
            // Create
            $pdo->prepare("INSERT INTO pokemon (name,type_primary,type_secondary,hp,attack,defense,speed,rarity,image_url,description) VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$name,$typePri,$typeSec,$hp,$attack,$defense,$speed,$rarity,$imageUrl,$description]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => $name . ' added to catalogue!'];
        }
        header('Location: /admin/pokemon.php'); exit;
    } else {
        $editing = $_POST; // Re-populate form with submitted data
        $editing['pokemon_id'] = $pokemonId;
    }
}

$allPokemon = $pdo->query("SELECT * FROM pokemon ORDER BY name")->fetchAll();
$types    = ['Fire','Water','Grass','Electric','Psychic','Ghost','Dragon','Dark','Fighting','Normal','Fairy','Rock','Steel','Poison','Flying','Ground','Ice','Bug'];
$rarities = ['Common','Rare','Epic','Legendary'];
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 px-4 py-4">
            <h1 class="h3 fw-bold mb-4">Pokémon Catalogue</h1>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <!-- Add / Edit Form -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h2 class="h6 fw-bold mb-3"><?= $editing ? 'Edit Pokémon' : 'Add New Pokémon' ?></h2>
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="pokemon_id" value="<?= $editing ? (int)$editing['pokemon_id'] : 0 ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= $editing ? e($editing['name']) : '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Primary Type *</label>
                            <select name="type_primary" class="form-select" required>
                                <option value="">—</option>
                                <?php foreach ($types as $t): ?>
                                <option value="<?= $t ?>" <?= ($editing && $editing['type_primary']===$t)?'selected':'' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Secondary Type</label>
                            <select name="type_secondary" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($types as $t): ?>
                                <option value="<?= $t ?>" <?= ($editing && ($editing['type_secondary']??'')===$t)?'selected':'' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">HP</label>
                            <input type="number" name="hp" class="form-control" min="1" max="255"
                                   value="<?= $editing ? (int)$editing['hp'] : 50 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Attack</label>
                            <input type="number" name="attack" class="form-control" min="1" max="255"
                                   value="<?= $editing ? (int)$editing['attack'] : 50 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Defense</label>
                            <input type="number" name="defense" class="form-control" min="1" max="255"
                                   value="<?= $editing ? (int)$editing['defense'] : 50 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Speed</label>
                            <input type="number" name="speed" class="form-control" min="1" max="255"
                                   value="<?= $editing ? (int)$editing['speed'] : 50 ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rarity</label>
                            <select name="rarity" class="form-select">
                                <?php foreach ($rarities as $r): ?>
                                <option value="<?= $r ?>" <?= ($editing && $editing['rarity']===$r)?'selected':'' ?>><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
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
                        <button type="submit" class="btn btn-pm-primary"><?= $editing ? 'Save Changes' : 'Add Pokémon' ?></button>
                        <?php if ($editing): ?><a href="/admin/pokemon.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Pokémon Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr><th>Image</th><th>Name</th><th>Types</th><th>Stats</th><th>Rarity</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allPokemon as $p): ?>
                            <tr>
                                <td><img src="<?= e($p['image_url']) ?>" alt="" style="width:44px;height:44px;object-fit:contain;background:#eef0ff;border-radius:8px;"></td>
                                <td class="fw-bold"><?= e($p['name']) ?></td>
                                <td>
                                    <span class="type-badge" style="background:<?= typeBadgeColor($p['type_primary']) ?>; font-size:0.7rem;"><?= e($p['type_primary']) ?></span>
                                    <?php if ($p['type_secondary']): ?>
                                    <span class="type-badge ms-1" style="background:<?= typeBadgeColor($p['type_secondary']) ?>; font-size:0.7rem;"><?= e($p['type_secondary']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small">HP:<?= $p['hp'] ?> ATK:<?= $p['attack'] ?> DEF:<?= $p['defense'] ?> SPD:<?= $p['speed'] ?></td>
                                <td><span class="badge rarity-<?= strtolower(e($p['rarity'])) ?>"><?= e($p['rarity']) ?></span></td>
                                <td>
                                    <a href="/admin/pokemon.php?edit=<?= $p['pokemon_id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete <?= e($p['name']) ?>?')">
                                        <input type="hidden" name="delete_id" value="<?= $p['pokemon_id'] ?>">
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

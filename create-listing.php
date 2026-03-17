<?php
// ============================================================
// create-listing.php — Create New Listing
// ============================================================
require_once 'includes/header.php';
$pageTitle = 'Create Listing';
requireLogin();

$pdo    = getPDO();
$errors = [];

// Fetch all pokemon for dropdown
$allPokemon = $pdo->query("SELECT pokemon_id, name, type_primary, rarity FROM pokemon ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pokemonId   = (int)($_POST['pokemon_id']   ?? 0);
    $title       = trim($_POST['title']          ?? '');
    $description = trim($_POST['description']    ?? '');
    $price       = (float)($_POST['price']       ?? 0);
    $stock       = (int)($_POST['stock']         ?? 1);

    if ($pokemonId <= 0)            $errors[] = 'Please select a Pokémon.';
    if (strlen($title) < 5)         $errors[] = 'Title must be at least 5 characters.';
    if ($price <= 0)                $errors[] = 'Price must be greater than 0.';
    if ($stock < 1 || $stock > 99) $errors[] = 'Stock must be between 1 and 99.';

    // Verify the pokemon_id exists
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT pokemon_id FROM pokemon WHERE pokemon_id = ?");
        $chk->execute([$pokemonId]);
        if (!$chk->fetch()) $errors[] = 'Invalid Pokémon selected.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO listings (seller_id, pokemon_id, title, description, price, stock)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $pokemonId, $title, $description, $price, $stock]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Listing created successfully!'];
        header('Location: /my-listings.php'); exit;
    }
}
?>

<div class="container py-5" style="max-width:640px;">
    <h1 class="h2 fw-bold mb-4" style="color:var(--pm-blue);">
        <i class="bi bi-plus-circle me-2"></i>Create a Listing
    </h1>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <form method="POST" class="needs-validation" novalidate>

            <!-- Pokemon Select -->
            <div class="mb-3">
                <label for="pokemon_id" class="form-label fw-semibold">Select Pokémon <span class="text-danger">*</span></label>
                <select class="form-select" id="pokemon_id" name="pokemon_id" required aria-describedby="pokemonHelp">
                    <option value="">— Choose a Pokémon —</option>
                    <?php foreach ($allPokemon as $p): ?>
                    <option value="<?= $p['pokemon_id'] ?>"
                        <?= (isset($_POST['pokemon_id']) && $_POST['pokemon_id'] == $p['pokemon_id']) ? 'selected' : '' ?>>
                        <?= e($p['name']) ?> (<?= e($p['type_primary']) ?> · <?= e($p['rarity']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <div id="pokemonHelp" class="form-text">Choose which Pokémon you are selling.</div>
                <div class="invalid-feedback">Please select a Pokémon.</div>
            </div>

            <!-- Title -->
            <div class="mb-3">
                <label for="title" class="form-label fw-semibold">Listing Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title"
                       value="<?= isset($_POST['title']) ? e($_POST['title']) : '' ?>"
                       required minlength="5" maxlength="150" placeholder="e.g. Shiny Pikachu — Level 50">
                <div class="invalid-feedback">Title must be at least 5 characters.</div>
            </div>

            <!-- Description -->
            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"
                          placeholder="Describe your Pokémon — nature, moves, IVs, etc."><?= isset($_POST['description']) ? e($_POST['description']) : '' ?></textarea>
            </div>

            <!-- Price & Stock -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label for="price" class="form-label fw-semibold">Price ($) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="price" name="price"
                               value="<?= isset($_POST['price']) ? e($_POST['price']) : '' ?>"
                               required min="0.01" step="0.01" placeholder="0.00">
                    </div>
                    <div class="invalid-feedback">Enter a valid price.</div>
                </div>
                <div class="col-6">
                    <label for="stock" class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="stock" name="stock"
                           value="<?= isset($_POST['stock']) ? e($_POST['stock']) : '1' ?>"
                           required min="1" max="99">
                    <div class="invalid-feedback">Quantity must be 1–99.</div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-pm-primary px-5">
                    <i class="bi bi-check-circle me-2"></i>Create Listing
                </button>
                <a href="/my-listings.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

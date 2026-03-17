<?php
// ============================================================
// create-listing.php — Create New Listing
// ============================================================
require_once 'includes/header.php';
$pageTitle = 'Create Listing';
requireLogin();

$pdo    = getPDO();
$errors = [];

// Fetch all cards for dropdown
$allCards = $pdo->query("SELECT card_id, card_name, set_name, typing, rarity FROM cards ORDER BY card_name")->fetchAll();
$conditions = ['PSA 1','PSA 2','PSA 3','PSA 4','PSA 5','PSA 6','PSA 7','PSA 8','PSA 9','PSA 10'];
$languages  = ['English','Japanese','Korean','Chinese','German','French','Spanish','Italian'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardId         = (int)($_POST['card_id']         ?? 0);
    $title          = trim($_POST['title']             ?? '');
    $description    = trim($_POST['description']       ?? '');
    $price          = (float)($_POST['price']          ?? 0);
    $stock          = (int)($_POST['stock']            ?? 1);
    $conditionGrade = trim($_POST['condition_grade']   ?? '');
    $language       = trim($_POST['language']          ?? '');

    if ($cardId <= 0)               $errors[] = 'Please select a card.';
    if (strlen($title) < 5)         $errors[] = 'Title must be at least 5 characters.';
    if ($price <= 0)                $errors[] = 'Price must be greater than 0.';
    if ($stock < 1 || $stock > 99)  $errors[] = 'Stock must be between 1 and 99.';
    if (!in_array($conditionGrade, $conditions)) $errors[] = 'Please select a condition.';
    if (!in_array($language, $languages))        $errors[] = 'Please select a language.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT card_id FROM cards WHERE card_id = ?");
        $chk->execute([$cardId]);
        if (!$chk->fetch()) $errors[] = 'Invalid card selected.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO listings (seller_id, card_id, title, description, price, stock, condition_grade, language)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $cardId, $title, $description, $price, $stock, $conditionGrade, $language]);
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

            <!-- Card Select -->
            <div class="mb-3">
                <label for="card_id" class="form-label fw-semibold">Select Card <span class="text-danger">*</span></label>
                <select class="form-select" id="card_id" name="card_id" required aria-describedby="cardHelp">
                    <option value="">— Choose a Card —</option>
                    <?php foreach ($allCards as $c): ?>
                    <option value="<?= $c['card_id'] ?>"
                        <?= (isset($_POST['card_id']) && $_POST['card_id'] == $c['card_id']) ? 'selected' : '' ?>>
                        <?= e($c['card_name']) ?> — <?= e($c['set_name']) ?> (<?= e($c['typing']) ?> · <?= e($c['rarity']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <div id="cardHelp" class="form-text">Choose which card you are selling.</div>
                <div class="invalid-feedback">Please select a card.</div>
            </div>

            <!-- Condition & Language -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label for="condition_grade" class="form-label fw-semibold">Condition <span class="text-danger">*</span></label>
                    <select class="form-select" id="condition_grade" name="condition_grade" required>
                        <option value="">— Select —</option>
                        <?php foreach ($conditions as $cond): ?>
                        <option value="<?= $cond ?>" <?= (isset($_POST['condition_grade']) && $_POST['condition_grade'] === $cond) ? 'selected' : '' ?>><?= $cond ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label for="language" class="form-label fw-semibold">Language <span class="text-danger">*</span></label>
                    <select class="form-select" id="language" name="language" required>
                        <option value="">— Select —</option>
                        <?php foreach ($languages as $lang): ?>
                        <option value="<?= $lang ?>" <?= (isset($_POST['language']) && $_POST['language'] === $lang) ? 'selected' : '' ?>><?= $lang ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
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
                          placeholder="Describe the card — condition details, centering, etc."><?= isset($_POST['description']) ? e($_POST['description']) : '' ?></textarea>
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

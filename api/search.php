<?php
// ============================================================
// api/search.php — Search Autocomplete
// ============================================================
require_once '../config/db.php';
header('Content-Type: application/json');

$q   = trim($_GET['q'] ?? '');
$pdo = getPDO();

if (strlen($q) < 2) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("
    SELECT DISTINCT c.card_name
    FROM cards c
    JOIN listings l ON l.card_id = c.card_id
    WHERE c.card_name LIKE ? AND l.status = 'active'
    ORDER BY c.card_name ASC
    LIMIT 8
");
$stmt->execute(["%$q%"]);
$results = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(array_map(fn($name) => ['name' => $name], $results));

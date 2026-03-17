<?php
// ============================================================
// config/db.php — Database Connection
// ============================================================

$db = parse_ini_file('/var/www/private/db_config.ini', true)['database'];
define('DB_HOST', $db['servername']);
define('DB_NAME', $db['dbname']);
define('DB_USER', $db['username']);
define('DB_PASS', $db['password']);

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed. Please check your config/db.php settings.");
        }
    }
    return $pdo;
}

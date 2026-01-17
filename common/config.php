<?php
// Config XAMPP/MySQL - ADATTA DB_NAME al tuo
define('DB_HOST', 'localhost');
define('DB_NAME', 'playroom');  // ← CAMBIA col tuo DB
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $GLOBALS['pdo'] = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'DB: ' . $e->getMessage()]));
}
?>

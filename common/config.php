<?php
// common/config.php
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'playroom_planner');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,              // errori come eccezioni [web:217]
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // fetch associativo di default [web:208]
            PDO::ATTR_EMULATE_PREPARES => false,                      // prepared statement reali (consigliato) [web:210]
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);

    // In consegna/lab meglio evitare dettagli sensibili; loggali se serve.
    // error_log($e->getMessage());

    die("Errore di connessione al database");
}

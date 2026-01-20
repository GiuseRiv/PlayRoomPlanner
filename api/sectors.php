<?php
declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json');

function ok($data) { 
    echo json_encode(['ok' => true, 'data' => $data]); 
    exit; 
}

try {
    // Recupera tutti i settori
    $stmt = $pdo->query("SELECT id_settore, nome FROM settore ORDER BY nome ASC");
    $settori = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // IMPORTANTE: Anche se vuoto, deve tornare un array []
    ok($settori); 
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
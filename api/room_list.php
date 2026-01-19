<?php
declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query(
        "SELECT s.id_sala,
                s.nome AS nome_sala,
                s.capienza,
                se.nome AS nome_settore,
                se.tipo
         FROM Sala s
         JOIN Settore se ON se.id_settore = s.id_settore
         ORDER BY se.nome, s.nome"
    );

    echo json_encode([
        'ok' => true,
        'data' => ['rooms' => $stmt->fetchAll(PDO::FETCH_ASSOC)]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Errore server']);
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';

header('Content-Type: application/json; charset=utf-8');

function json_error(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
   
    json_error(401, 'Non autorizzato');
}

$idSala = isset($_GET['id_sala']) ? (int)$_GET['id_sala'] : 0;
if ($idSala <= 0) {
    json_error(400, 'Parametro id_sala mancante o non valido');
}

try {
    
    $stmt = $pdo->prepare(
        "SELECT s.id_sala,
                s.nome AS nome_sala,
                s.capienza,
                se.id_settore,
                se.nome AS nome_settore,
                se.tipo
         FROM Sala s
         JOIN Settore se ON se.id_settore = s.id_settore
         WHERE s.id_sala = :id"
    );
    $stmt->execute([':id' => $idSala]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        json_error(404, 'Sala non trovata');
    }

  
    $stmt2 = $pdo->prepare(
        "SELECT d.id_dotazione, d.nome
         FROM contiene c
         JOIN Dotazione d ON d.id_dotazione = c.id_dotazione
         WHERE c.id_sala = :id
         ORDER BY d.nome"
    );
    $stmt2->execute([':id' => $idSala]);
    $equipments = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'data' => [
            'room' => $room,
            'equipments' => $equipments
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    json_error(500, 'Errore server');
}

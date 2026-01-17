<?php
// STESSI HEADER + require config come sopra
header('Content-Type: application/json; charset=utf-8');
// ... headers CORS

require_once '../common/config.php';
$pdo = $GLOBALS['pdo'];

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        // TODO: Controllo sovrapposizioni
        $stmt = $pdo->prepare("INSERT INTO prenotazioni (sala_id, organizzatore_id, inizio, fine, titolo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['sala_id'], 1 /* TODO session user */, $data['inizio'], $data['fine'], $data['titolo']]);
        http_response_code(201);
        echo json_encode(['id' => $pdo->lastInsertId()]);
    }
    // GET, PUT, DELETE IDENTICI a users.php (copia/incolla adattando tabella)
    // ...
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

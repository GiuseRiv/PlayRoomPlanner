<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../common/config.php';
$pdo = $GLOBALS['pdo'];

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO utenti (nome, cognome, email, ruolo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['nome'], $data['cognome'] ?? '', $data['email'], $data['ruolo'] ?? 'allievo']);
        http_response_code(201);
        echo json_encode(['id' => $pdo->lastInsertId()]);
    } elseif ($method === 'GET') {
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM utenti WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($user ?: ['error' => 'Non trovato']);
        } else {
            $stmt = $pdo->query("SELECT * FROM utenti");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    } elseif ($method === 'PUT' && $id) {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE utenti SET nome=?, cognome=?, email=? WHERE id=?");
        $stmt->execute([$data['nome'], $data['cognome'], $data['email'], $id]);
        echo json_encode(['updated' => $stmt->rowCount() > 0]);
    } elseif ($method === 'DELETE' && $id) {
        $stmt = $pdo->prepare("DELETE FROM utenti WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['deleted' => $stmt->rowCount() > 0]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Metodo non valido']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

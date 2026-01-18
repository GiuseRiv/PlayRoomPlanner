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
        // Legge il corpo della richiesta JSON
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception("Dati non validi");
        }

        $stmt = $pdo->prepare("INSERT INTO Iscritto (nome, cognome, email, ruolo, data_nascita) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['nome'], 
            $data['cognome'], 
            $data['email'], 
            $data['ruolo'] ?? 'allievo',
            $data['data_nascita']
        ]);
        
        http_response_code(201);
        echo json_encode(['status' => 'success', 'id_iscritto' => $pdo->lastInsertId()]);
    } elseif ($method === 'GET') {
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM Iscritto WHERE id_iscritto = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            echo json_encode($user ?: ['error' => 'Utente non trovato']);
        } else {
            $stmt = $pdo->query("SELECT * FROM Iscritto");
            echo json_encode($stmt->fetchAll());
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Metodo non supportato']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
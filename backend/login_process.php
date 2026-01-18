<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php'; // avvia sessione + $pdo
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metodo non supportato']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? null; // se lo userete in futuro

if ($email === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Email obbligatoria']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_iscritto, nome, ruolo, email FROM Iscritto WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // Nota: per ora fate login solo per email; se aggiungete password, verificate qui.
    if ($user) {
        $_SESSION['user_id'] = (int)$user['id_iscritto'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_ruolo'] = $user['ruolo'];

        echo json_encode(['status' => 'success', 'message' => 'Login effettuato con successo']);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Email non trovata o credenziali errate']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Errore server']);
}

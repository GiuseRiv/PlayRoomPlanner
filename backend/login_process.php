<?php
require_once('../common/config.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM Iscritto WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Rigenera ID sessione per sicurezza (Prevenzione Session Fixation)
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id_iscritto'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_ruolo'] = $user['ruolo'];
        
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Email o password errati']);
    }
}
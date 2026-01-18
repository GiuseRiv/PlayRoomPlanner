<?php
session_start();
require_once('../common/config.php');

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    
    // In a real project, we would verify a password here
    $sql = "SELECT * FROM Iscritto WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Save user data in session
        $_SESSION['user_id'] = $user['id_iscritto'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_ruolo'] = $user['ruolo'];
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Login effettuato con successo'
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Email non trovata o credenziali errate'
        ]);
    }
}
?>
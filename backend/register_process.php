<?php
require_once('../common/config.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $email = $_POST['email'];
    $data_nascita = $_POST['data_nascita'];
    
    // Hash della password (BCRYPT)
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Gestione Foto
    $foto_name = 'default.png';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_name = uniqid('user_') . '.' . $ext;
        if (!is_dir('../uploads')) mkdir('../uploads');
        move_uploaded_file($_FILES['foto']['tmp_name'], '../uploads/' . $foto_name);
    }

    try {
        $sql = "INSERT INTO Iscritto (nome, cognome, email, password, data_nascita, foto) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $cognome, $email, $password, $data_nascita, $foto_name]);
        
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email già registrata']);
    }
}
<?php
require_once('../common/config.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $cognome = $_POST['cognome'] ?? '';
    $email = $_POST['email'] ?? '';
    $ruolo = $_POST['ruolo'] ?? 'allievo';
    $data_nascita = $_POST['data_nascita'] ?? '';
    $password = $_POST['password'] ?? '';

    // 1. Hash della Password (Requisito di sicurezza)
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);

    // 2. Gestione Foto
    $foto_db_name = 'default.png'; // Immagine di default
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        // Genero un nome unico per evitare sovrascritture
        $foto_db_name = uniqid('user_') . '.' . $file_ext;
        
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_db_name);
    }

    try {
        // 3. Inserimento nel Database
        $sql = "INSERT INTO Iscritto (nome, cognome, email, password, ruolo, data_nascita, foto) 
                VALUES (:nome, :cognome, :email, :password, :ruolo, :data_nascita, :foto)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nome' => $nome,
            'cognome' => $cognome,
            'email' => $email,
            'password' => $password_hashed,
            'ruolo' => $ruolo,
            'data_nascita' => $data_nascita,
            'foto' => $foto_db_name
        ]);
        
        echo json_encode(['status' => 'success', 'message' => 'Registrazione completata']);
    } catch (PDOException $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Errore nel database: ' . $e->getMessage()]);
    }
}
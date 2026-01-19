<?php
require_once('../common/config.php');
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metodo non consentito']);
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$cognome = trim($_POST['cognome'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$data_nascita = $_POST['data_nascita'] ?? '';

// FORZATURA RUOLO: Ogni nuovo iscritto è un allievo
$ruolo = 'allievo';

// Validazione server-side
if (!$nome || !$cognome || !$email || !$data_nascita || strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dati mancanti o non validi']);
    exit;
}

if ($password !== $password_confirm) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Le password non coincidono']);
    exit;
}

try {
    // Controllo email duplicata
    $checkEmail = $pdo->prepare("SELECT id_iscritto FROM Iscritto WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Questa email è già registrata']);
        exit;
    }

    $password_hashed = password_hash($password, PASSWORD_BCRYPT);

    // Gestione Foto
    $foto_db_name = 'default.png';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_db_name = uniqid('user_') . '.' . $file_ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_db_name);
    }

    $sql = "INSERT INTO Iscritto (nome, cognome, email, password, ruolo, data_nascita, foto) 
            VALUES (:nome, :cognome, :email, :password, :ruolo, :data_nascita, :foto)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'nome' => $nome,
        'cognome' => $cognome,
        'email' => $email,
        'password' => $password_hashed,
        'ruolo' => $ruolo, // Sarà sempre 'allievo'
        'data_nascita' => $data_nascita,
        'foto' => $foto_db_name
    ]);
    
    echo json_encode(['status' => 'success', 'message' => 'Registrazione completata']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Errore database: ' . $e->getMessage()]);
}
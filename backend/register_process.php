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

$ruolo = 'allievo';

// Validazione Password lato server
$hasUpper = preg_match('@[A-Z]@', $password);
$hasSpecial = preg_match('@[^\w]@', $password);

if (!$email || strlen($password) < 8 || !$hasUpper || !$hasSpecial) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Requisiti password non rispettati o dati mancanti.']);
    exit;
}

if ($password !== $password_confirm) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Le password non coincidono.']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id_iscritto FROM Iscritto WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Email già registrata.']);
        exit;
    }

    $password_hashed = password_hash($password, PASSWORD_BCRYPT);
    $foto_name = 'default.png';

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_name = uniqid('user_') . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_name);
    }

    $sql = "INSERT INTO Iscritto (nome, cognome, email, password, ruolo, data_nascita, foto) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$nome, $cognome, $email, $password_hashed, $ruolo, $data_nascita, $foto_name]);
    
    echo json_encode(['status' => 'success', 'message' => 'Registrazione completata']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Errore DB: ' . $e->getMessage()]);
}
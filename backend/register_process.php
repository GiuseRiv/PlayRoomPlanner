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

// --- 1. VALIDAZIONE DATI PERSONALI ---
if (strlen($nome) < 2 || strlen($cognome) < 2) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nome e Cognome devono avere almeno 2 caratteri.']);
    exit;
}

if ($data_nascita) {
    $oggi = date('Y-m-d');
    if ($data_nascita > $oggi) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'La data di nascita non può essere nel futuro.']);
        exit;
    }
    if ($data_nascita < '1900-01-01') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Data di nascita non valida.']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'La data di nascita è obbligatoria.']);
    exit;
}

// --- 2. VALIDAZIONE PASSWORD E EMAIL ---
$hasUpper = preg_match('@[A-Z]@', $password);
$hasSpecial = preg_match('@[^\w]@', $password);

if (!$email || strlen($password) < 8 || !$hasUpper || !$hasSpecial) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Requisiti password non rispettati o email non valida.']);
    exit;
}

if ($password !== $password_confirm) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Le password non coincidono.']);
    exit;
}

try {
    // Controllo email esistente
    $check = $pdo->prepare("SELECT id_iscritto FROM Iscritto WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(['status' => 'error', 'message' => 'Email già registrata.']);
        exit;
    }

    // Hash password
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);
    
    // Gestione Foto
    $foto_name = 'default.png';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        // Validazione estensione base
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $foto_name = uniqid('user_') . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_name);
        }
    }

    // Inserimento
    $sql = "INSERT INTO Iscritto (nome, cognome, email, password, ruolo, data_nascita, foto) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$nome, $cognome, $email, $password_hashed, $ruolo, $data_nascita, $foto_name]);
    
    echo json_encode(['status' => 'success', 'message' => 'Registrazione completata']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Errore DB: ' . $e->getMessage()]);
}
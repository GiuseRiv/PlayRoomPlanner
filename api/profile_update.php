<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php'; // Fondamentale: richiede login

header('Content-Type: application/json');

// Accetta solo POST (compatibile con FormData)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$uid = (int)$_SESSION['user_id']; // ID preso dalla sessione, non dal form!

// Recupera dati testuali
$nome = trim($_POST['nome'] ?? '');
$cognome = trim($_POST['cognome'] ?? '');
$data_nascita = $_POST['data_nascita'] ?? '';

// --- VALIDAZIONE AGGIUNTIVA ---

// 1. Controllo campi vuoti
if (strlen($nome) < 2 || strlen($cognome) < 2) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Nome e Cognome devono avere almeno 2 caratteri']);
    exit;
}

// 2. Controllo Data di Nascita (Non futura e formato valido)
if ($data_nascita) {
    $oggi = date('Y-m-d');
    
    // Controllo se futura
    if ($data_nascita > $oggi) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'La data di nascita non può essere nel futuro']);
        exit;
    }

    // Controllo opzionale: non troppo nel passato (es. 1900)
    if ($data_nascita < '1900-01-01') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Data di nascita non valida']);
        exit;
    }
} else {
    // Se la data è obbligatoria, scommenta questo:
    /*
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'La data di nascita è obbligatoria']);
    exit; 
    */
}

// --- FINE VALIDAZIONE ---

try {
    // 1. Gestione FOTO (Logica presa dalla tua registrazione)
    $upload_dir = __DIR__ . '/../uploads/'; // Cartella usata in registrazione
    $foto_sql_part = ""; 
    $params = [$nome, $cognome, $data_nascita];

    // Se c'è un file valido
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        // Usa uniqid come nella tua registrazione
        $foto_name = uniqid('user_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_name)) {
            // Aggiungiamo aggiornamento foto alla query
            $foto_sql_part = ", foto = ?";
            $params[] = $foto_name; // Salviamo solo il nome file, come in registrazione
        } else {
             throw new Exception("Errore spostamento file");
        }
    }

    // 2. Query SQL Dinamica
    // Aggiorna sempre Nome, Cognome, Nascita. Aggiorna foto solo se caricata.
    // WHERE id_iscritto = ID sessione (sicurezza)
    $params[] = $uid; 
    
    $sql = "UPDATE Iscritto 
            SET nome = ?, cognome = ?, data_nascita = ? $foto_sql_part 
            WHERE id_iscritto = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    // Aggiorniamo la sessione corrente con i nuovi dati!
    $_SESSION['user_nome'] = $nome;
    $_SESSION['user_cognome'] = $cognome; 
    // (Opzionale: se la dashboard mostra la foto dalla sessione, dovresti aggiornare anche quella, 
    // ma di solito la foto viene caricata al volo o è meglio non metterla in sessione se pesa).
    echo json_encode(['ok' => true, 'message' => 'Profilo aggiornato', 'foto' => $foto_name ?? null]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Errore server: ' . $e->getMessage()]);
}
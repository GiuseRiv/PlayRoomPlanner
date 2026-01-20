<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php'; 

header('Content-Type: application/json');


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$uid = (int)$_SESSION['user_id']; 


$nome = trim($_POST['nome'] ?? '');
$cognome = trim($_POST['cognome'] ?? '');
$data_nascita = $_POST['data_nascita'] ?? '';




if (strlen($nome) < 2 || strlen($cognome) < 2) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Nome e Cognome devono avere almeno 2 caratteri']);
    exit;
}


if ($data_nascita) {
    $oggi = date('Y-m-d');
    
    
    if ($data_nascita > $oggi) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'La data di nascita non può essere nel futuro']);
        exit;
    }

    // non troppo nel passato (1900)
    if ($data_nascita < '1900-01-01') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Data di nascita non valida']);
        exit;
    }
}


try {
    
    $upload_dir = __DIR__ . '/../Images/';
    $foto_sql_part = ""; 
    $params = [$nome, $cognome, $data_nascita];

    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        
        $foto_name = uniqid('user_') . '.' . $ext;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_name)) {
            
            $foto_sql_part = ", foto = ?";
            $params[] = $foto_name;
        } else {
             throw new Exception("Errore spostamento file");
        }
    }

    $params[] = $uid; 
    
    $sql = "UPDATE Iscritto 
            SET nome = ?, cognome = ?, data_nascita = ? $foto_sql_part 
            WHERE id_iscritto = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $_SESSION['user_nome'] = $nome;
    $_SESSION['user_cognome'] = $cognome; 
    
    echo json_encode(['ok' => true, 'message' => 'Profilo aggiornato', 'foto' => $foto_name ?? null]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Errore server: ' . $e->getMessage()]);
}
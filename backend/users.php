<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$uid = (int)($_SESSION['user_id'] ?? 0);

try {
  
  if ($method === 'GET') {
    if ($id > 0) {
      
      $sql = "SELECT 
                i.id_iscritto, i.nome, i.cognome, i.ruolo, i.email, i.foto, i.data_nascita,
                s.nome AS nome_settore_resp, s.anni_servizio, s.data_nomina
            FROM Iscritto i
            LEFT JOIN Settore s ON i.id_iscritto = s.id_responsabile
            WHERE i.id_iscritto = ?";
      
      $st = $pdo->prepare($sql);
      $st->execute([$id]);
      $u = $st->fetch(PDO::FETCH_ASSOC);
      
      if (!$u) err('Utente non trovato', 404);

      $st2 = $pdo->prepare("SELECT id_settore FROM afferisce WHERE id_iscritto=?");
      $st2->execute([$id]);
      $u['settori_ids'] = $st2->fetchAll(PDO::FETCH_COLUMN);

      ok($u);
    } else {
      
      $st = $pdo->query("SELECT id_iscritto, nome, cognome, ruolo, email, foto FROM Iscritto");
      ok($st->fetchAll(PDO::FETCH_ASSOC));
    }
  }

  
  if ($method === 'POST' && isset($_FILES['foto'])) {
      if ($id !== $uid) err('Puoi modificare solo la tua foto', 403);
      
      $file = $_FILES['foto'];
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'gif'];
      
      if (!in_array($ext, $allowed)) err('Formato non valido. Usa JPG o PNG.');
      if ($file['size'] > 2 * 1024 * 1024) err('File troppo grande (max 2MB).');

      
      $newDetails = $id . '_' . time() . '.' . $ext;
      $destPath = __DIR__ . '/../Images/' . $newDetails;

      if (!move_uploaded_file($file['tmp_name'], $destPath)) {
          err('Errore nel salvataggio del file.');
      }

      
      $st = $pdo->prepare("UPDATE Iscritto SET foto = ? WHERE id_iscritto = ?");
      $st->execute([$newDetails, $id]);

      ok(['file' => $newDetails, 'message' => 'Foto aggiornata']);
  }

 
  if ($method === 'POST' && !isset($_FILES['foto'])) {
   
    $data = json_decode(file_get_contents('php://input'), true);
  }

 
 if ($method === 'PUT') {
    if ($id <= 0) err('id mancante', 422);
    if ($id !== $uid) err('Puoi modificare solo il tuo profilo', 403);

    $data = json_decode(file_get_contents('php://input'), true);

    
    if (isset($data['old_password']) && isset($data['new_password'])) {
        
        $oldPass = $data['old_password'];
        $newPass = $data['new_password'];

        
        $q = $pdo->prepare("SELECT password FROM Iscritto WHERE id_iscritto = ?");
        $q->execute([$id]);
        $currentHash = $q->fetchColumn();

        
        if (!$currentHash || !password_verify($oldPass, $currentHash)) {
           
            err('La password attuale inserita non è corretta.', 401);
        }

        
        $hasUpper = preg_match('@[A-Z]@', $newPass);
        $hasSpecial = preg_match('@[^\w]@', $newPass);

        if (strlen($newPass) < 8 || !$hasUpper || !$hasSpecial) {
            err('La nuova password non rispetta i requisiti di sicurezza.', 400);
        }

        
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE Iscritto SET password = ? WHERE id_iscritto = ?");
        $upd->execute([$newHash, $id]);

        ok(['message' => 'Password aggiornata con successo']);
    }
    

    $nome = trim((string)($data['nome'] ?? ''));
    $cognome = trim((string)($data['cognome'] ?? ''));
    
    
    $fields = []; $params = [];
    if ($nome !== '') { $fields[] = "nome=?"; $params[] = $nome; }
    if ($cognome !== '') { $fields[] = "cognome=?"; $params[] = $cognome; }
    
    if (!$fields) err('Nessun campo valido inviato', 422);

    $params[] = $id;
    $sql = "UPDATE Iscritto SET " . implode(', ', $fields) . " WHERE id_iscritto=?";
    $st = $pdo->prepare($sql);
    $st->execute($params);

    ok(['message' => 'Profilo aggiornato']);
  }

} catch (Exception $e) {
  err('Errore server: '.$e->getMessage(), 500);
}
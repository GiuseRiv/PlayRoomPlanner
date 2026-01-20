<?php declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json');

function ok($data){ echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

if ($_SESSION['user_ruolo'] !== 'tecnico') err('Solo tecnici',403);

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$id = (int)($body['id_iscritto'] ?? 0);
if ($id <= 0) err('ID mancante');

$ruolo = trim($body['ruolo'] ?? '');
$settori = $body['settori'] ?? []; // array id_settore

try {
  $pdo->beginTransaction();
  
  // 1. Update ruolo
  if ($ruolo) {
    $st = $pdo->prepare('UPDATE Iscritto SET ruolo=? WHERE id_iscritto=?');
    $st->execute([$ruolo, $id]);
  }
  
  // 2. Sostituisci afferisce (DELETE + INSERT)
  $pdo->prepare('DELETE FROM afferisce WHERE id_iscritto=?')->execute([$id]);
  if ($settori) {
    $st = $pdo->prepare('INSERT INTO afferisce (id_iscritto, id_settore) VALUES (?,?)');
    foreach ($settori as $sid) $st->execute([$id, (int)$sid]);
  }
  
  $pdo->commit();
  ok(['message'=>'Utente aggiornato']);
  
} catch (Exception $e) {
  $pdo->rollBack();
  err($e->getMessage());
}
?>

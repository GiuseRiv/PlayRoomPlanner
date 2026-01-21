<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Metodo non supportato', 405);

$body = json_decode(file_get_contents('php://input'), true);
$idPren = (int)($body['id_prenotazione'] ?? 0);
if ($idPren <= 0) err('id_prenotazione mancante', 422);

$uid = (int)$_SESSION['user_id'];


try {
  $stmt = $pdo->prepare("
    SELECT p.data, p.ora_inizio 
    FROM Prenotazione p JOIN invito i ON i.id_prenotazione = p.id_prenotazione 
    WHERE i.id_iscritto = ? AND i.id_prenotazione = ? AND i.stato = 'accettato'
  ");
  $stmt->execute([$uid, $idPren]);
  $inv = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$inv) err('Nessuna accettazione attiva da rimuovere', 404);
  
  $dtInizio = new DateTime($inv['data'].' '.sprintf('%02d:00:00',$inv['ora_inizio']));
  if ($dtInizio < new DateTime()) err('⏰ Impegno scaduto: non più modificabile', 410);
  
  $stmt = $pdo->prepare("
    UPDATE invito
    SET stato='pendente', data_risposta=NOW()
    WHERE id_iscritto=? AND id_prenotazione=? AND stato='accettato'
  ");
  $stmt->execute([$uid, $idPren]);
  
  
  if ($stmt->rowCount() === 0) err('Nessuna disponibilità da rimuovere (invito non accettato?)', 409);
  ok(['message'=>'Rimosso']);
} catch (Exception $e) {
  err('Errore server', 500);
}

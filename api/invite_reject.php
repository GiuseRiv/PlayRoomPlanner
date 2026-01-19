<?php
// VINCOLI APPLICATI: motivazione obbligatoria
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Metodo non supportato', 405);

$body = json_decode(file_get_contents('php://input'), true);
$idPren = (int)($body['id_prenotazione'] ?? 0);
$mot = trim((string)($body['motivazione_rifiuto'] ?? ''));

if ($idPren <= 0) err('id_prenotazione mancante', 422);
if ($mot === '') err('Motivazione obbligatoria', 422);

$uid = (int)$_SESSION['user_id'];

try {
  $stmt = $pdo->prepare("
    UPDATE invito
    SET stato='rifiutato', data_risposta=NOW(), motivazione_rifiuto=?
    WHERE id_iscritto=? AND id_prenotazione=?
  ");
  $stmt->execute([$mot, $uid, $idPren]);

  if ($stmt->rowCount() === 0) err('Invito non trovato', 404);
  ok(['message'=>'Rifiutato']);
} catch (Exception $e) {
  err('Errore server', 500);
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void {
  http_response_code($code);
  echo json_encode(['ok'=>true,'data'=>$data]);
  exit;
}
function err(string $m, int $code=400): void {
  http_response_code($code);
  echo json_encode(['ok'=>false,'message'=>$m]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Metodo non supportato', 405);

$uid = (int)$_SESSION['user_id'];

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) err('JSON non valido', 422);

$idPren          = (int)($body['id_prenotazione'] ?? 0);
$mode            = (string)($body['mode'] ?? '');      
$idSettore       = (int)($body['id_settore'] ?? 0);    
$ruolo           = (string)($body['ruolo'] ?? '');     
$includeOrganizer = (bool)($body['include_organizer'] ?? false);

if ($idPren <= 0) err('id_prenotazione mancante', 422);
if (!in_array($mode, ['all','sector','role'], true)) err('mode non valido', 422);

try {
  $pdo->beginTransaction();

  
  $st = $pdo->prepare("
    SELECT p.id_prenotazione, p.id_organizzatore, p.id_sala, p.stato, s.capienza
    FROM Prenotazione p
    JOIN Sala s ON s.id_sala = p.id_sala
    WHERE p.id_prenotazione = ?
    FOR UPDATE
  ");
  $st->execute([$idPren]);
  $p = $st->fetch();
  if (!$p) err('Prenotazione non trovata', 404);
  if ((int)$p['id_organizzatore'] !== $uid) err('Non autorizzato', 403);
  if ($p['stato'] !== 'confermata') err('Prenotazione non confermata', 409);

  $capienza = (int)$p['capienza'];

  
  $st = $pdo->prepare("SELECT COUNT(*) AS n FROM invito WHERE id_prenotazione=?");
  $st->execute([$idPren]);
  $alreadyInvited = (int)$st->fetch()['n'];

  
  if ($mode === 'all') {
    $sqlCand = "SELECT id_iscritto FROM Iscritto";
    $paramsCand = [];

  } elseif ($mode === 'sector') {
    if ($idSettore <= 0) err('id_settore mancante', 422);
    $sqlCand = "
      SELECT a.id_iscritto
      FROM afferisce a
      JOIN Iscritto u ON u.id_iscritto = a.id_iscritto
      WHERE a.id_settore = ?
    ";
    $paramsCand = [$idSettore];

  } else { 
    if ($ruolo === '') err('ruolo mancante', 422);
    $sqlCand = "SELECT id_iscritto FROM Iscritto WHERE ruolo = ?";
    $paramsCand = [$ruolo];
  }
 
  if (!$includeOrganizer) {

    $tablePrefix = match($mode) {
        'sector' => 'a.',
        'role', 'all' => ''
    };
    
    $sqlCand .= (stripos($sqlCand, 'WHERE') === false)
      ? " WHERE {$tablePrefix}id_iscritto <> ?"
      : " AND {$tablePrefix}id_iscritto <> ?";
    $paramsCand[] = $uid;
}

  $st = $pdo->prepare($sqlCand);
  $st->execute($paramsCand);
  $candidates = $st->fetchAll();

  if (!$candidates) {
    $pdo->commit();
    ok(['inserted' => 0, 'skipped_existing' => 0, 'message' => 'Nessun candidato trovato']);
  }

  
  $ins = $pdo->prepare("
    INSERT IGNORE INTO invito (id_iscritto, id_prenotazione, data_invio, stato)
    VALUES (?, ?, CURDATE(), 'pendente')
  ");

  $inserted = 0;
  $skipped  = 0;

  foreach ($candidates as $row) {
    $idIscritto = (int)$row['id_iscritto'];

    $ins->execute([$idIscritto, $idPren]);
    if ($ins->rowCount() > 0) $inserted++;
    else $skipped++;
  }

  $pdo->commit();
  ok([
    'id_prenotazione'   => $idPren,
    'mode'              => $mode,
    'inserted'          => $inserted,
    'skipped_existing'  => $skipped
  ], 201);

} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log("inviti_create ERROR: " . $e->getMessage() . " | Line: " . $e->getLine() . " | JSON: " . file_get_contents('php://input'));
  err('Errore server: ' . $e->getMessage(), 500);
}

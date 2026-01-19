<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Metodo non supportato', 405);

$uid = (int)$_SESSION['user_id'];

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) err('JSON non valido', 422);

$idPren = (int)($body['id_prenotazione'] ?? 0);
$mode = (string)($body['mode'] ?? ''); // 'all' | 'sector' | 'role'
$idSettore = (int)($body['id_settore'] ?? 0); // richiesto se mode='sector'
$ruolo = (string)($body['ruolo'] ?? '');      // richiesto se mode='role'
$includeOrganizer = (bool)($body['include_organizer'] ?? false);

if ($idPren <= 0) err('id_prenotazione mancante', 422);
if (!in_array($mode, ['all','sector','role'], true)) err('mode non valido', 422);

try {
  $pdo->beginTransaction();

  // 1) Verifica prenotazione + permesso (solo organizzatore può invitare)
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

  // 2) Conta già invitati (pendente/accettato/rifiutato: per la capienza conta chi accetta, ma
  //    per “non invitare più persone dei posti” puoi decidere una politica)
  $st = $pdo->prepare("SELECT COUNT(*) AS n FROM invito WHERE id_prenotazione=?");
  $st->execute([$idPren]);
  $alreadyInvited = (int)$st->fetch()['n'];

  // Politica consigliata (semplice): non creare più inviti di capienza * 3? (non richiesta)
  // Qui imponiamo una regola minima: non invitare più di capienza * 1 se vuoi essere “strict”.
  // Se preferisci invitare più persone della capienza (e accettano fino a esaurimento), commenta questo blocco.
  // ----
  // if ($alreadyInvited >= $capienza) err('Capienza raggiunta: troppi invitati già inseriti', 409);
  // ----
  // La consegna richiede solo che gli ACCETTATI non superino capienza; invitare > capienza è plausibile. [file:1]

  // 3) Costruisci lista candidati in base alla modalità
  if ($mode === 'all') {
    $sqlCand = "SELECT id_iscritto FROM Iscritto";
    $paramsCand = [];
  } elseif ($mode === 'sector') {
    if ($idSettore <= 0) err('id_settore mancante', 422);
    $sqlCand = "SELECT id_iscritto FROM afferisce WHERE id_settore = ?";
    $paramsCand = [$idSettore];
  } else { // role
    if ($ruolo === '') err('ruolo mancante', 422);
    $sqlCand = "SELECT id_iscritto FROM Iscritto WHERE ruolo = ?";
    $paramsCand = [$ruolo];
  }

  if (!$includeOrganizer) {
    // escludi organizzatore
    $sqlCand .= (stripos($sqlCand, 'WHERE') === false) ? " WHERE id_iscritto <> ?" : " AND id_iscritto <> ?";
    $paramsCand[] = $uid;
  }

  $st = $pdo->prepare($sqlCand);
  $st->execute($paramsCand);
  $candidates = $st->fetchAll();

  if (!$candidates) {
    $pdo->commit();
    ok(['inserted' => 0, 'skipped_existing' => 0, 'message' => 'Nessun candidato trovato']);
  }

  // 4) Inserimento inviti: evita duplicati (PK composta id_iscritto,id_prenotazione)
  // Nota: MySQL supporta INSERT IGNORE (semplice) oppure ON DUPLICATE KEY.
  $ins = $pdo->prepare("
    INSERT IGNORE INTO invito (id_iscritto, id_prenotazione, data_invio, stato)
    VALUES (?, ?, CURDATE(), 'pendente')
  ");

  $inserted = 0;
  $skipped = 0;

  foreach ($candidates as $row) {
    $idIscritto = (int)$row['id_iscritto'];

    // opzionale: non invitare oltre capienza (strict)
    // Se vuoi essere “strict”, sblocca queste 3 righe:
    // if (($alreadyInvited + $inserted) >= $capienza) break;

    $ins->execute([$idIscritto, $idPren]);
    if ($ins->rowCount() > 0) $inserted++;
    else $skipped++;
  }

  $pdo->commit();
  ok([
    'id_prenotazione' => $idPren,
    'mode' => $mode,
    'inserted' => $inserted,
    'skipped_existing' => $skipped
  ], 201);

} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  err('Errore server', 500);
}

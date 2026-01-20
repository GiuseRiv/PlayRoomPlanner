<?php
// VINCOLI applicati: non superare la capienza (conta accettati), non avere sovrapposizioni con altre prenotazioni già accettate dall’utente

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

function ok($data = null, int $code=200): void {
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

$body = json_decode(file_get_contents('php://input'), true);
$idPren = (int)($body['id_prenotazione'] ?? 0);
if ($idPren <= 0) err('id_prenotazione mancante', 422);

$uid = (int)$_SESSION['user_id'];

try {
  $pdo->beginTransaction();

  // blocco la riga invito (se esiste)
  $stmt = $pdo->prepare("SELECT stato FROM invito WHERE id_iscritto=? AND id_prenotazione=? FOR UPDATE");
  $stmt->execute([$uid, $idPren]);
  $inv = $stmt->fetch();
  if (!$inv) err('Invito non trovato', 404);
  if ($inv['stato'] === 'accettato') ok(['message'=>'Già accettato']);

  // dati prenotazione + sala
  $stmt = $pdo->prepare("
    SELECT p.id_prenotazione, p.data, p.ora_inizio, p.durata_ore, p.id_sala, s.capienza
    FROM Prenotazione p
    JOIN Sala s ON s.id_sala = p.id_sala
    WHERE p.id_prenotazione = ?
    FOR UPDATE
  ");
  $stmt->execute([$idPren]);
  $p = $stmt->fetch();
  if (!$p) err('Prenotazione non trovata', 404);

  // capienza: conta accettati
  $stmt = $pdo->prepare("
    SELECT (
        1 +  -- ← ORGANIZZATORE SEMPRE contato
        (SELECT COUNT(*) FROM invito WHERE id_prenotazione=? AND stato='accettato')
    ) AS n_accettati_totali
");
$stmt->execute([$idPren]);
$nAcc = (int)$stmt->fetch()['n_accettati_totali'];
if ($nAcc >= (int)$p['capienza']) err('Posti esauriti', 409);

  // sovrapposizione con altre prenotazioni ACCETTATE dall'utente nello stesso giorno
  // Intervalli: [ora_inizio, ora_inizio+durata_ore)
  $stmt = $pdo->prepare("
    SELECT p2.id_prenotazione
    FROM invito i2
    JOIN Prenotazione p2 ON p2.id_prenotazione = i2.id_prenotazione
    WHERE i2.id_iscritto = ?
      AND i2.stato = 'accettato'
      AND p2.data = ?
      AND NOT (
        (p2.ora_inizio + p2.durata_ore) <= ?
        OR ( ? + ? ) <= p2.ora_inizio
      )
    LIMIT 1
  ");
  $stmt->execute([
    $uid,
    $p['data'],
    (int)$p['ora_inizio'],
    (int)$p['ora_inizio'],
    (int)$p['durata_ore']
  ]);
  $over = $stmt->fetch();
  if ($over) err('Hai già un impegno sovrapposto in quell’orario', 409);

  // aggiorno stato invito
  $stmt = $pdo->prepare("
    UPDATE invito
    SET stato='accettato', data_risposta=NOW(), motivazione_rifiuto=NULL
    WHERE id_iscritto=? AND id_prenotazione=?
  ");
  $stmt->execute([$uid, $idPren]);

  $pdo->commit();
  ok(['message'=>'Accettato']);
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  err('Errore server', 500);
}

<?php
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

  // 🔧 1. VERIFICA SCADUTO + STATO PENDENTE
  $stmt = $pdo->prepare("
    SELECT i.stato, p.data, p.ora_inizio
    FROM invito i
    JOIN Prenotazione p ON p.id_prenotazione = i.id_prenotazione
    WHERE i.id_iscritto = ? AND i.id_prenotazione = ?
    FOR UPDATE
  ");
  $stmt->execute([$uid, $idPren]);
  $inv = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$inv) err('Invito non trovato', 404);
  if ($inv['stato'] !== 'pendente') err('Invito già gestito', 409);
  
  // 🔧 CALCOLO SCADUTO
  $dtInizio = new DateTime($inv['data'] . ' ' . sprintf('%02d:00:00', $inv['ora_inizio']));
  if ($dtInizio < new DateTime()) err('⏰ Impegno scaduto: non più accettabile', 410);

  // 2. VERIFICA CAPienza (corretto tuo codice)
  $stmt = $pdo->prepare("
    SELECT p.id_prenotazione, p.data, p.ora_inizio, p.durata_ore, p.id_sala, s.capienza
    FROM Prenotazione p JOIN Sala s ON s.id_sala = p.id_sala
    WHERE p.id_prenotazione = ? FOR UPDATE
  ");
  $stmt->execute([$idPren]);
  $p = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$p) err('Prenotazione non trovata', 404);

  $stmt = $pdo->prepare("
    SELECT COUNT(*) AS n_accettati
    FROM invito WHERE id_prenotazione = ? AND stato = 'accettato'
  ");
  $stmt->execute([$idPren]);
  $nAcc = 1 + (int)$stmt->fetch()['n_accettati']; // +1 per organizzatore
  if ($nAcc > (int)$p['capienza']) err('Posti esauriti (' . $nAcc . '/' . $p['capienza'] . ')', 409);

  // 3. VERIFICA SOVRAPPOSIZIONI (corretto tuo codice)
  $stmt = $pdo->prepare("
    SELECT 1 FROM Prenotazione p2
    JOIN invito i2 ON i2.id_prenotazione = p2.id_prenotazione
    WHERE i2.id_iscritto = ? AND i2.stato = 'accettato'
      AND p2.data = ?
      AND p2.stato = 'confermata'
      AND NOT (
        (p2.ora_inizio + p2.durata_ore) <= ?
        OR (? + ?) <= p2.ora_inizio
      )
    LIMIT 1
  ");
  $stmt->execute([$uid, $p['data'], (int)$p['ora_inizio'], (int)$p['ora_inizio'], (int)$p['durata_ore']]);
  if ($stmt->fetch()) err('Hai già un impegno sovrapposto quel giorno', 409);

  // 4. UPDATE
  $stmt = $pdo->prepare("
    UPDATE invito 
    SET stato = 'accettato', data_risposta = NOW(), motivazione_rifiuto = NULL
    WHERE id_iscritto = ? AND id_prenotazione = ?
  ");
  $stmt->execute([$uid, $idPren]);

  $pdo->commit();
  ok(['message' => '✅ Invito accettato con successo']);
  
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('Accept error: ' . $e->getMessage());
  err('Errore server interno', 500);
}
?>

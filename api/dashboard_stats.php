<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../common/config.php';

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

if (!isset($_SESSION['user_id'])) err('Non autenticato', 401);

$uid = (int)$_SESSION['user_id'];
$today = date('Y-m-d');

try {
  // 1) inviti pendenti da oggi in poi
  $stmt = $pdo->prepare("
    SELECT COUNT(*) AS n
    FROM invito i
    JOIN Prenotazione p ON p.id_prenotazione = i.id_prenotazione
    WHERE i.id_iscritto = ?
      AND i.stato = 'pendente'
      AND p.data >= ?
  ");
  $stmt->execute([$uid, $today]);
  $pending = (int)$stmt->fetch()['n'];

  // 2) inviti accettati futuri
  $stmt = $pdo->prepare("
    SELECT COUNT(*) AS n
    FROM invito i
    JOIN Prenotazione p ON p.id_prenotazione = i.id_prenotazione
    WHERE i.id_iscritto = ?
      AND i.stato = 'accettato'
      AND p.data >= ?
  ");
  $stmt->execute([$uid, $today]);
  $acceptedFuture = (int)$stmt->fetch()['n'];

  // 3) prossima prova (come invitato accettato oppure organizzatore)
  $stmt = $pdo->prepare("
    SELECT
      p.id_prenotazione, p.data, p.ora_inizio, p.durata_ore, p.attivita,
      s.nome AS nome_sala
    FROM Prenotazione p
    JOIN Sala s ON s.id_sala = p.id_sala
    LEFT JOIN invito i ON i.id_prenotazione = p.id_prenotazione AND i.id_iscritto = ?
    WHERE p.data >= ?
      AND (p.id_organizzatore = ? OR i.stato = 'accettato')
    ORDER BY p.data ASC, p.ora_inizio ASC
    LIMIT 1
  ");
  $stmt->execute([$uid, $today, $uid]);
  $nextEvent = $stmt->fetch() ?: null;

  ok([
    'today' => $today,
    'pending_invites' => $pending,
    'accepted_future' => $acceptedFuture,
    'next_event' => $nextEvent
  ]);

} catch (Exception $e) {
  err('Errore server', 500);
}

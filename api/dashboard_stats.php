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

$uid = (int)$_SESSION['user_id'];
$today = date('Y-m-d');
$nowHour = (int)date('G');

try {
  // 1) INVITI PENDENTI
  // CORREZIONE: Aggiunto "AND p.stato = 'confermata'"
  // Conta solo se l'evento esiste ancora ed è confermato.
  $stmt = $pdo->prepare("
    SELECT COUNT(*) AS n
    FROM invito i
    JOIN Prenotazione p ON p.id_prenotazione = i.id_prenotazione
    WHERE i.id_iscritto = ?
      AND i.stato = 'pendente'
      AND p.stato = 'confermata'  -- <--- QUESTA RIGA MANCAVA
      AND p.data >= ?
  ");
  $stmt->execute([$uid, $today]);
  $pending = (int)$stmt->fetchColumn();

  // 2) Impegni Settimana
  $stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT p.id_prenotazione)
    FROM Prenotazione p
    LEFT JOIN invito i ON i.id_prenotazione = p.id_prenotazione
      AND i.id_iscritto = ? AND i.stato = 'accettato'
    WHERE YEARWEEK(p.data, 1) = YEARWEEK(?, 1)
      AND p.stato = 'confermata' 
      AND (p.id_organizzatore = ? OR i.id_iscritto IS NOT NULL)
  ");
  $stmt->execute([$uid, $today, $uid]);
  $impegniSettimana = (int)$stmt->fetchColumn();

  // 3) Prossimo impegno
  $stmt = $pdo->prepare("
    SELECT
      p.id_prenotazione, p.data, p.ora_inizio, p.durata_ore, p.attivita,
      s.nome AS nome_sala
    FROM Prenotazione p
    JOIN Sala s ON s.id_sala = p.id_sala
    LEFT JOIN invito i
      ON i.id_prenotazione = p.id_prenotazione
      AND i.id_iscritto = ?
    WHERE p.stato = 'confermata'
      AND (
        p.data > ?
        OR (p.data = ? AND p.ora_inizio >= ?)
      )
      AND (p.id_organizzatore = ? OR i.stato = 'accettato')
    ORDER BY p.data ASC, p.ora_inizio ASC
    LIMIT 1
  ");
  $stmt->execute([$uid, $today, $today, $nowHour, $uid]);
  
  $nextEvent = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

  ok([
    'today' => $today,
    'pending_invites' => $pending,
    'planned_week' => $impegniSettimana,
    'next_event' => $nextEvent
  ]);

} catch (Exception $e) {
  err('Errore server', 500);
}
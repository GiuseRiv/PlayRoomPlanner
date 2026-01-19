<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';
header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

$uid = (int)$_SESSION['user_id'];

try {
  // Lista inviti dal giorno corrente in poi, ordinati per data/ora (requisito)
  $stmt = $pdo->prepare("
    SELECT
      p.id_prenotazione,
      p.data,
      p.ora_inizio,
      p.durata_ore,
      p.attivita,
      s.nome AS nome_sala,
      i.stato AS stato_invito
    FROM invito i
    JOIN Prenotazione p ON p.id_prenotazione = i.id_prenotazione
    JOIN Sala s ON s.id_sala = p.id_sala
    WHERE i.id_iscritto = :uid
      AND p.data >= CURDATE()
      AND p.stato = 'confermata'
    ORDER BY p.data ASC, p.ora_inizio ASC
  ");
  $stmt->execute(['uid' => $uid]);

  ok(['invites' => $stmt->fetchAll()]);
} catch (Exception $e) {
  err('Errore server', 500);
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';
header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

$uid = (int)$_SESSION['user_id'];

try {
  $stmt = $pdo->prepare("
    SELECT
      p.id_prenotazione,
      p.data,
      p.ora_inizio,
      p.durata_ore,
      p.attivita,
      s.nome AS nome_sala,
      i.stato AS stato_invito,
      CONCAT(p.data, ' ', LPAD(p.ora_inizio, 2, '0'), ':00:00') AS datetime_inizio
    FROM invito i
    JOIN Prenotazione p ON p.id_prenotazione = i.id_prenotazione
    JOIN Sala s ON s.id_sala = p.id_sala
    WHERE i.id_iscritto = :uid
    ORDER BY p.data ASC, p.ora_inizio ASC
  ");
  $stmt->execute(['uid' => $uid]);

  $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 🔧 LOGICA SCADUTO
  foreach ($invites as &$inv) {
    $dtInizio = new DateTime($inv['datetime_inizio']);
    $now = new DateTime();
    $inv['stato_effettivo'] = ($dtInizio < $now && $inv['stato_invito'] === 'pendente')
      ? 'scaduto'
      : $inv['stato_invito'];
    unset($inv['datetime_inizio']); // Pulizia
  }

  ok(['invites' => $invites]);
} catch (Exception $e) {
  err('Errore server', 500);
}
?>

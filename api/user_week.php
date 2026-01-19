<?php
declare(strict_types=1);

ob_start();
ini_set('display_errors', '0');

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  ob_clean();
  echo json_encode(['ok' => false, 'message' => 'Non autenticato']);
  exit;
}

$day = $_GET['day'] ?? date('Y-m-d');
$ts = strtotime($day);
if ($ts === false) {
  http_response_code(400);
  ob_clean();
  echo json_encode(['ok' => false, 'message' => 'Parametro day non valido']);
  exit;
}

$dow = (int)date('N', $ts);
$mondayTs = strtotime('-' . ($dow - 1) . ' day', $ts);
$sundayTs = strtotime('+' . (7 - $dow) . ' day', $ts);

$monday = date('Y-m-d', $mondayTs);
$sunday = date('Y-m-d', $sundayTs);

$userId = (int)$_SESSION['user_id'];

$sql = "
SELECT
  p.id_prenotazione,
  p.data,
  p.ora_inizio,
  p.durata_ore,
  p.attivita,
  s.nome AS nome_sala,
  i.stato AS stato_invito,
  CONCAT(org.nome, ' ', org.cognome) AS organizzatore
FROM invito i
JOIN Prenotazione p ON p.id_prenotazione = i.id_prenotazione
JOIN Sala s ON s.id_sala = p.id_sala
JOIN Iscritto org ON org.id_iscritto = p.id_organizzatore
WHERE i.id_iscritto = :uid
  AND p.data BETWEEN :monday AND :sunday
ORDER BY p.data ASC, p.ora_inizio ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  'uid' => $userId,
  'monday' => $monday,
  'sunday' => $sunday
]);

$rows = $stmt->fetchAll();

$out = [];
$rejected = [];

foreach ($rows as $r) {
  $row = [
    'data' => $r['data'],
    'ora' => (string)((int)$r['ora_inizio']) . ':00',
    'durata' => (string)((int)$r['durata_ore']) . 'h',
    'sala' => $r['nome_sala'],
    'attivita' => $r['attivita'],
    'organizzatore' => $r['organizzatore'],
    'stato_invito' => $r['stato_invito'],
  ];

  if ($r['stato_invito'] === 'rifiutato') $rejected[] = $row;
  else $out[] = $row;
}

ob_clean();
echo json_encode([
  'ok' => true,
  'data' => $out,
  'week' => ['monday' => $monday, 'sunday' => $sunday],
  'rejected' => $rejected
]);
exit;

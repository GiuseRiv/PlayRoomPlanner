<?php
declare(strict_types=1);

ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  ob_clean();
  echo json_encode(['ok' => false, 'message' => 'Non autenticato'], JSON_UNESCAPED_UNICODE);
  exit;
}

$day = $_GET['day'] ?? date('Y-m-d');
$ts = strtotime($day);
if ($ts === false) {
  http_response_code(400);
  ob_clean();
  echo json_encode(['ok' => false, 'message' => 'Parametro day non valido'], JSON_UNESCAPED_UNICODE);
  exit;
}

// lun-dom ISO
$dow = (int)date('N', $ts);
$mondayTs = strtotime('-' . ($dow - 1) . ' day', $ts);
$sundayTs = strtotime('+' . (7 - $dow) . ' day', $ts);

$monday = date('Y-m-d', $mondayTs);
$sunday = date('Y-m-d', $sundayTs);

$userId = (int)$_SESSION['user_id'];

$sql = "
SELECT
  x.id_prenotazione,
  x.id_organizzatore,
  x.data,
  x.ora_inizio,
  x.durata_ore,
  x.attivita,
  x.nome_sala,
  x.organizzatore,
  x.stato_invito
FROM (
  -- A) Sono invitato
  SELECT
    p.id_prenotazione,
    p.id_organizzatore,
    p.data,
    p.ora_inizio,
    p.durata_ore,
    p.attivita,
    s.nome AS nome_sala,
    CONCAT(org.nome, ' ', org.cognome) AS organizzatore,
    i.stato AS stato_invito
  FROM invito i
  JOIN Prenotazione p ON p.id_prenotazione = i.id_prenotazione
  JOIN Sala s ON s.id_sala = p.id_sala
  JOIN Iscritto org ON org.id_iscritto = p.id_organizzatore
  WHERE i.id_iscritto = :uid
    AND p.data BETWEEN :monday AND :sunday
    AND p.stato = 'confermata'

  UNION

  -- B) Sono organizzatore (anche se non ho invito)
  SELECT
    p.id_prenotazione,
    p.id_organizzatore,
    p.data,
    p.ora_inizio,
    p.durata_ore,
    p.attivita,
    s.nome AS nome_sala,
    CONCAT(org.nome, ' ', org.cognome) AS organizzatore,
    COALESCE(i2.stato, 'organizzatore') AS stato_invito
  FROM Prenotazione p
  JOIN Sala s ON s.id_sala = p.id_sala
  JOIN Iscritto org ON org.id_iscritto = p.id_organizzatore
  LEFT JOIN invito i2
    ON i2.id_prenotazione = p.id_prenotazione
   AND i2.id_iscritto = :uid2
  WHERE p.id_organizzatore = :uid3
    AND p.data BETWEEN :monday2 AND :sunday2
    AND p.stato = 'confermata'
) AS x
ORDER BY x.data ASC, x.ora_inizio ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  'uid' => $userId,
  'monday' => $monday,
  'sunday' => $sunday,
  'uid2' => $userId,
  'uid3' => $userId,
  'monday2' => $monday,
  'sunday2' => $sunday,
]);

$rows = $stmt->fetchAll();

$out = [];
$rejected = [];

foreach ($rows as $r) {
  $isOrganizer = ((int)$r['id_organizzatore'] === $userId);

  $row = [
    'id_prenotazione' => (int)$r['id_prenotazione'],
    'data' => $r['data'],
    'ora' => str_pad((string)((int)$r['ora_inizio']), 2, '0', STR_PAD_LEFT) . ':00',
    'durata' => (string)((int)$r['durata_ore']) . 'h',
    'sala' => $r['nome_sala'],
    'attivita' => $r['attivita'] ?? '',
    'organizzatore' => $r['organizzatore'],
    'stato_invito' => $r['stato_invito'],
    'can_cancel' => $isOrganizer
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
], JSON_UNESCAPED_UNICODE);
exit;

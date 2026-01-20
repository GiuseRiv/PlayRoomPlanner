<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

$roomId = isset($_GET['roomId']) ? (int)$_GET['roomId'] : 0;
$day = $_GET['day'] ?? date('Y-m-d');
$ts = strtotime($day);
if ($roomId <= 0) err('roomId mancante', 422);
if ($ts === false) err('day non valido', 422);


$dow = (int)date('N', $ts);
$monday = date('Y-m-d', strtotime('-'.($dow-1).' day', $ts));
$sunday = date('Y-m-d', strtotime('+'.(7-$dow).' day', $ts));

$stmt = $pdo->prepare("
  SELECT
    p.id_prenotazione, p.data, p.ora_inizio, p.durata_ore, p.attivita, p.stato,
    CONCAT(org.nome,' ',org.cognome) AS organizzatore
  FROM Prenotazione p
  JOIN Iscritto org ON org.id_iscritto = p.id_organizzatore
  WHERE p.id_sala = :rid
    AND p.data BETWEEN :monday AND :sunday
    AND p.stato = 'confermata'
  ORDER BY p.data ASC, p.ora_inizio ASC
");
$stmt->execute(['rid'=>$roomId, 'monday'=>$monday, 'sunday'=>$sunday]);

ok(['monday'=>$monday, 'sunday'=>$sunday, 'rows'=>$stmt->fetchAll()]);

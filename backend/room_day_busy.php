<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m], JSON_UNESCAPED_UNICODE); exit; }

$idSala = isset($_GET['id_sala']) ? (int)$_GET['id_sala'] : 0;
$date   = isset($_GET['date']) ? (string)$_GET['date'] : '';

if ($idSala <= 0) err('id_sala mancante', 422);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) err('date non valida (YYYY-MM-DD)', 422);

$st = $pdo->prepare("
  SELECT ora_inizio, durata_ore
  FROM Prenotazione
  WHERE id_sala = ?
    AND data = ?
    AND stato = 'confermata'
");
$st->execute([$idSala, $date]);
$rows = $st->fetchAll();

$busy = [];
foreach ($rows as $r) {
  $start = (int)$r['ora_inizio'];
  $dur   = (int)$r['durata_ore'];
  
  for ($h = $start; $h < ($start + $dur); $h++) {
    if ($h >= 9 && $h <= 22) $busy[$h] = true;
  }
}


$busyHours = array_map('intval', array_keys($busy));
sort($busyHours);

ok(['id_sala' => $idSala, 'date' => $date, 'busy_hours' => $busyHours]);

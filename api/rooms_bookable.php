<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void {
  http_response_code($code);
  echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE);
  exit;
}
function err(string $m, int $code=400): void {
  http_response_code($code);
  echo json_encode(['ok'=>false,'message'=>$m], JSON_UNESCAPED_UNICODE);
  exit;
}

$uid = (int)$_SESSION['user_id'];
$ruolo = (string)($_SESSION['user_ruolo'] ?? '');

if ($ruolo === 'tecnico') {
  $st = $pdo->query("
    SELECT s.id_sala,
           s.nome AS nome_sala,
           s.capienza,
           se.id_settore,
           se.nome AS nome_settore,
           se.tipo
    FROM Sala s
    JOIN Settore se ON se.id_settore = s.id_settore
    ORDER BY se.nome, s.nome
  ");
  ok(['rooms' => $st->fetchAll()]);
}

$st = $pdo->prepare("
  SELECT s.id_sala,
         s.nome AS nome_sala,
         s.capienza,
         se.id_settore,
         se.nome AS nome_settore,
         se.tipo
  FROM Sala s
  JOIN Settore se ON se.id_settore = s.id_settore
  WHERE se.id_responsabile = :uid
     OR se.tipo IN (
       SELECT se2.tipo
       FROM Settore se2
       WHERE se2.id_responsabile = :uid2
     )
  ORDER BY se.nome, s.nome
");
$st->execute(['uid' => $uid, 'uid2' => $uid]);

ok(['rooms' => $st->fetchAll()]);

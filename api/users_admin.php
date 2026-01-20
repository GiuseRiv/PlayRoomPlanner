<?php declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data){ echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

if ($_SESSION['user_ruolo'] !== 'tecnico') err('Solo tecnici',403);

$role = $_GET['role'] ?? '';
$search = trim($_GET['search'] ?? '');
$sector = (int)($_GET['sector'] ?? 0);

$sql = "
  SELECT 
    u.id_iscritto, u.nome, u.cognome, u.ruolo, u.email, u.data_nascita, 
    GROUP_CONCAT(DISTINCT s.nome SEPARATOR ', ') AS settori
  FROM Iscritto u 
  LEFT JOIN afferisce a ON a.id_iscritto = u.id_iscritto
  LEFT JOIN Settore s ON s.id_settore = a.id_settore
";

$params = [];
$where = [];

if ($role) { $where[] = 'u.ruolo = ?'; $params[] = $role; }
if ($search) { 
  $where[] = "(u.nome LIKE ? OR u.cognome LIKE ? OR u.email LIKE ?)"; 
  $s = "%$search%";
  $params[] = $params[] = $params[] = $s; 
}
if ($sector) { $where[] = 'a.id_settore = ?'; $params[] = $sector; }

if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' GROUP BY u.id_iscritto ORDER BY u.cognome, u.nome';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

ok($stmt->fetchAll());
?>

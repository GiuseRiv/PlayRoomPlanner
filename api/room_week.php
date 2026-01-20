<?php
declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data=null){ http_response_code(200); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err($m,$code=400){ http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

$idSala = (int)($_GET['id_sala'] ?? 0);
$day = $_GET['day'] ?? date('Y-m-d');

if ($idSala <= 0) err('id_sala mancante');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$day)) err('day invalido');

// Lun-Dom ISO
$d = new DateTime($day);
$monday = $d->modify('monday this week')->format('Y-m-d');
$sunday = (new DateTime($monday))->modify('sunday this week')->format('Y-m-d');

$stmt = $pdo->prepare("
  SELECT p.id_prenotazione, p.data, p.ora_inizio, p.durata_ore, p.attivita,
         CONCAT(i.nome,' ',i.cognome) AS organizzatore
  FROM Prenotazione p JOIN Iscritto i ON i.id_iscritto = p.id_organizzatore
  WHERE p.id_sala=? AND p.stato='confermata' AND p.data BETWEEN ? AND ?
  ORDER BY p.data, p.ora_inizio
");
$stmt->execute([$idSala, $monday, $sunday]);
$bookings = $stmt->fetchAll();

ok(['bookings' => $bookings]);  // ← Struttura per rooms_week.js

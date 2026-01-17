<?php
// Header + config
require_once '../common/config.php';
$pdo = $GLOBALS['pdo'];

$roomId = $_GET['roomId'] ?? null;
$day = $_GET['day'] ?? date('Y-m-d');

// Settimana lun-dom
$start = (new DateTime($day))->modify('monday this week')->format('Y-m-d 00:00:00');
$end = (new DateTime($day))->modify('sunday this week')->format('Y-m-d 23:59:59');

$stmt = $pdo->prepare("SELECT * FROM prenotazioni WHERE sala_id=? AND inizio BETWEEN ? AND ? ORDER BY inizio");
$stmt->execute([$roomId, $start, $end]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

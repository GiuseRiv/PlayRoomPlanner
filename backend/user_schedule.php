<?php

require_once '../common/config.php';
$pdo = $GLOBALS['pdo'];

$userId = $_GET['userId'] ?? null;
$day = $_GET['day'] ?? date('Y-m-d');


$start = (new DateTime($day))->modify('monday this week')->format('Y-m-d 00:00:00');
$end = (new DateTime($day))->modify('sunday this week')->format('Y-m-d 23:59:59');

$stmt = $pdo->prepare("
    SELECT p.* FROM prenotazioni p 
    WHERE organizzatore_id=? AND inizio BETWEEN ? AND ? 
    ORDER BY inizio
");
$stmt->execute([$userId, $start, $end]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

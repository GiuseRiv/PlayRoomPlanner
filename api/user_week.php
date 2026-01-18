<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Non autenticato']);
    exit;
}

require_once __DIR__ . '/../common/config.php';

$day = $_GET['day'] ?? date('Y-m-d');
$ts = strtotime($day);
if ($ts === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Parametro day non valido']);
    exit;
}

// calcolo lun-dom (ISO): lun=1..dom=7
$dow = (int)date('N', $ts);
$mondayTs = strtotime('-' . ($dow - 1) . ' day', $ts);
$sundayTs = strtotime('+' . (7 - $dow) . ' day', $ts);

$monday = date('Y-m-d', $mondayTs);
$sunday = date('Y-m-d', $sundayTs);

$userId = (int)$_SESSION['user_id'];

/**
 * Ritorna inviti della settimana per stato (accettato/pendente/rifiutato).
 * Nota: Prenotazione.data è DATE.
 */
$sql = "
SELECT
  p.id_prenotazione,
  p.data,
  p.ora_inizio,
  p.durata_ore,
  p.attivita,
  p.id_sala,
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

$events = [];
$rejected = [];

foreach ($rows as $r) {
    $item = [
        'id_prenotazione' => (int)$r['id_prenotazione'],
        'data' => $r['data'],
        'ora_inizio' => (int)$r['ora_inizio'],
        'durata_ore' => (int)$r['durata_ore'],
        'attivita' => $r['attivita'],
        'id_sala' => (int)$r['id_sala'],
        'nome_sala' => $r['nome_sala'],
        'stato_invito' => $r['stato_invito'],
        'organizzatore' => $r['organizzatore'],
    ];

    if ($r['stato_invito'] === 'rifiutato') {
        $rejected[] = $item;
    } else {
        $events[] = $item;
    }
}

echo json_encode([
    'ok' => true,
    'data' => [
        'monday' => $monday,
        'sunday' => $sunday,
        'events' => $events,
        'rejected' => $rejected
    ]
]);

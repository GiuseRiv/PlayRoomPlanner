<?php
declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';

header('Content-Type: application/json; charset=utf-8');

$idSala = isset($_GET['id_sala']) ? (int)$_GET['id_sala'] : 0;
$day    = $_GET['day'] ?? '';

if ($idSala <= 0 || !$day) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Parametri mancanti']);
    exit;
}

try {
    $d = new DateTimeImmutable($day);
    // ISO: lunedì=1 ... domenica=7
    $start = $d->modify('-' . ((int)$d->format('N') - 1) . ' days')->format('Y-m-d');
    $end   = (new DateTimeImmutable($start))->modify('+6 days')->format('Y-m-d');

    $stmt = $pdo->prepare(
        "SELECT p.id_prenotazione,
                p.data,
                p.ora_inizio,
                p.durata_ore,
                p.attivita,
                CONCAT(i.nome, ' ', i.cognome) AS organizzatore
         FROM Prenotazione p
         JOIN Iscritto i ON i.id_iscritto = p.id_organizzatore
         WHERE p.id_sala = :id_sala
           AND p.stato = 'confermata'
           AND p.data BETWEEN :start AND :end
         ORDER BY p.data, p.ora_inizio"
    );
    $stmt->execute([':id_sala' => $idSala, ':start' => $start, ':end' => $end]);

    $bookings = array_map(function($r) {
        $r['when'] = $r['data'] . ' ' . str_pad((string)$r['ora_inizio'], 2, '0', STR_PAD_LEFT) . ':00';
        return $r;
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    echo json_encode([
        'ok' => true,
        'data' => [
            'range' => ['start' => $start, 'end' => $end],
            'bookings' => $bookings
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Errore server']);
}

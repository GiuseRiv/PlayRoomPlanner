<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sqlRooms = "
        SELECT s.nome, COUNT(p.id_prenotazione) as totale
        FROM Sala s
        LEFT JOIN Prenotazione p ON s.id_sala = p.id_sala
        GROUP BY s.id_sala, s.nome
        ORDER BY totale DESC
    ";
    $stmtRooms = $pdo->query($sqlRooms);
    $statsRooms = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);

    $sqlUsers = "
        SELECT i.nome, i.cognome, COUNT(p.id_prenotazione) as totale
        FROM Iscritto i
        JOIN Prenotazione p ON i.id_iscritto = p.id_organizzatore
        GROUP BY i.id_iscritto, i.nome, i.cognome
        ORDER BY totale DESC
        LIMIT 5
    ";
    $stmtUsers = $pdo->query($sqlUsers);
    $statsUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'data' => [
            'rooms' => $statsRooms,
            'users' => $statsUsers
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Errore Database: ' . $e->getMessage()]);
}
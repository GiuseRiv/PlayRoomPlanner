<?php
// api/schedules.php
require_once '../common/config.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? ''; // 'sala' oppure 'utente'
$id = $_GET['id'] ?? null;   // id_sala oppure id_iscritto
$data_rif = $_GET['data'] ?? date('Y-m-d'); // Giorno di riferimento per la settimana

if (!$id) {
    echo json_encode(['error' => 'ID mancante']);
    exit;
}

try {
    if ($type === 'sala') {
        // --- PUNTO 2: Prenotazioni per Sala per una data settimana ---
        $sql = "SELECT p.*, i.nome as organizzatore_nome, i.cognome as organizzatore_cognome
                FROM Prenotazione p
                JOIN Iscritto i ON p.id_organizzatore = i.id_iscritto
                WHERE p.id_sala = ? 
                AND YEARWEEK(p.data, 1) = YEARWEEK(?, 1)
                ORDER BY p.data, p.ora_inizio";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $data_rif]);
        echo json_encode($stmt->fetchAll());

    } elseif ($type === 'utente') {
        // --- PUNTO 3: Impegni per Utente per una data settimana ---
        // Include sia le prenotazioni organizzate dall'utente, sia quelle a cui ha accettato l'invito
        $sql = "SELECT DISTINCT p.*, s.nome as sala_nome
                FROM Prenotazione p
                JOIN Sala s ON p.id_sala = s.id_sala
                LEFT JOIN invito inv ON p.id_prenotazione = inv.id_prenotazione
                WHERE (p.id_organizzatore = ? OR (inv.id_iscritto = ? AND inv.stato = 'accettato'))
                AND YEARWEEK(p.data, 1) = YEARWEEK(?, 1)
                ORDER BY p.data, p.ora_inizio";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $id, $data_rif]);
        echo json_encode($stmt->fetchAll());

    } else {
        throw new Exception("Tipo di visualizzazione non valido");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
<?php

require_once '../common/config.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? ''; 
$id = $_GET['id'] ?? null;   
$data_rif = $_GET['data'] ?? date('Y-m-d');

if (!$id) {
    echo json_encode(['error' => 'ID mancante']);
    exit;
}

try {
    if ($type === 'sala') {
        
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
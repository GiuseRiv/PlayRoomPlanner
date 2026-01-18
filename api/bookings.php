<?php
// api/bookings.php
require_once '../common/config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null; // ID prenotazione per PUT e DELETE

try {
    // Legge i dati JSON in ingresso per POST e PUT
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'POST': // --- INSERIMENTO ---
            // Verifica sovrapposizione (Req. 3c)
            $sql_overlap = "SELECT COUNT(*) FROM Prenotazione 
                            WHERE id_sala = ? AND data = ? 
                            AND NOT (ora_inizio >= ? OR (ora_inizio + durata_ore) <= ?)";
            $stmt = $pdo->prepare($sql_overlap);
            $stmt->execute([$input['id_sala'], $input['data'], ($input['ora_inizio'] + $input['durata_ore']), $input['ora_inizio']]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("La sala è già occupata in questa fascia oraria.");
            }

            $sql = "INSERT INTO Prenotazione (data, ora_inizio, durata_ore, attivita, id_sala, id_organizzatore) VALUES (?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $input['data'], $input['ora_inizio'], $input['durata_ore'], 
                $input['attivita'], $input['id_sala'], $_SESSION['user_id']
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Prenotazione inserita']);
            break;

        case 'PUT': // --- MODIFICA ---
            if (!$id) throw new Exception("ID prenotazione mancante");
            
            // Verifica sovrapposizione escludendo la prenotazione stessa
            $sql_overlap = "SELECT COUNT(*) FROM Prenotazione 
                            WHERE id_sala = ? AND data = ? AND id_prenotazione != ? 
                            AND NOT (ora_inizio >= ? OR (ora_inizio + durata_ore) <= ?)";
            $stmt = $pdo->prepare($sql_overlap);
            $stmt->execute([$input['id_sala'], $input['data'], $id, ($input['ora_inizio'] + $input['durata_ore']), $input['ora_inizio']]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Modifica fallita: la sala è occupata da un'altra prenotazione.");
            }

            $sql = "UPDATE Prenotazione SET data = ?, ora_inizio = ?, durata_ore = ?, attivita = ?, id_sala = ? WHERE id_prenotazione = ?";
            $pdo->prepare($sql)->execute([
                $input['data'], $input['ora_inizio'], $input['durata_ore'], 
                $input['attivita'], $input['id_sala'], $id
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Prenotazione aggiornata']);
            break;

        case 'DELETE': // --- CANCELLAZIONE ---
            if (!$id) throw new Exception("ID prenotazione mancante");
            $pdo->prepare("DELETE FROM Prenotazione WHERE id_prenotazione = ?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Prenotazione cancellata']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Metodo non supportato']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
<?php declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($d){ echo json_encode(['ok'=>true, 'data'=>$d]); exit; }
function err($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false, 'message'=>$m]); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_ruolo'] ?? '';

// === GET: Carica dati per il form ===
if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) err('ID mancante');

    // 1. Recupera Prenotazione
    $stmt = $pdo->prepare("SELECT * FROM Prenotazione WHERE id_prenotazione = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) err('Prenotazione non trovata', 404);

    // Controllo permessi: Solo Tecnico o Organizzatore
    if ($userRole !== 'tecnico' && $booking['id_organizzatore'] != $userId) {
        err('Non hai i permessi per modificare questa prenotazione', 403);
    }

    // 2. Recupera Lista Sale (per la select)
    $sale = $pdo->query("SELECT id_sala, nome, capienza FROM Sala ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

    ok(['booking' => $booking, 'sale' => $sale]);
}

// === POST: Salva Modifiche ===
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = (int)($input['id_prenotazione'] ?? 0);
    $attivita = trim($input['attivita'] ?? '');
    $data = $input['data'] ?? '';
    $idSala = (int)($input['id_sala'] ?? 0);
    $oraInizio = (int)($input['ora_inizio'] ?? 0);
    $durata = (int)($input['durata_ore'] ?? 0);

    if ($id <= 0 || !$attivita || !$data || $idSala <= 0 || $durata <= 0) err('Dati incompleti');

    // Controllo Permessi (di nuovo, per sicurezza)
    $stmtCheck = $pdo->prepare("SELECT id_organizzatore FROM Prenotazione WHERE id_prenotazione = ?");
    $stmtCheck->execute([$id]);
    $orgId = $stmtCheck->fetchColumn();

    if (!$orgId) err('Prenotazione inesistente', 404);
    if ($userRole !== 'tecnico' && $orgId != $userId) err('Accesso negato', 403);

    // --- CONTROLLO SOVRAPPOSIZIONI ---
    // Importante: AND id_prenotazione != ? per escludere se stessi dal controllo
    $oraFine = $oraInizio + $durata;

    $sqlConflict = "
        SELECT COUNT(*) 
        FROM Prenotazione 
        WHERE id_sala = ? 
          AND data = ? 
          AND stato != 'annullata'
          AND id_prenotazione != ?  -- Esclude la prenotazione attuale
          AND (
            (ora_inizio < ?) AND (? < (ora_inizio + durata_ore))
          )
    ";
    
    $stmtConf = $pdo->prepare($sqlConflict);
    $stmtConf->execute([$idSala, $data, $id, $oraFine, $oraInizio]);
    
    if ($stmtConf->fetchColumn() > 0) {
        err("La sala è già occupata in questo orario (conflitto con altre prenotazioni).");
    }

    // UPDATE
    try {
        $upd = $pdo->prepare("
            UPDATE Prenotazione 
            SET attivita = ?, data = ?, id_sala = ?, ora_inizio = ?, durata_ore = ? 
            WHERE id_prenotazione = ?
        ");
        $upd->execute([$attivita, $data, $idSala, $oraInizio, $durata, $id]);
        
        ok(['message' => 'Modifica salvata']);
    } catch (Exception $e) {
        err('Errore database: ' . $e->getMessage());
    }
}
?>
<?php declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

// ... (Intestazioni e funzioni helper uguali a prima) ...
header('Content-Type: application/json; charset=utf-8');
function ok($d){ echo json_encode(['ok'=>true, 'data'=>$d]); exit; }
function err($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false, 'message'=>$m]); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_ruolo'] ?? '';

// GET (uguale a prima)
if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    // ... solita logica di recupero dati ...
    $stmt = $pdo->prepare("SELECT * FROM Prenotazione WHERE id_prenotazione = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) err('Prenotazione non trovata', 404);
    if ($userRole !== 'tecnico' && $booking['id_organizzatore'] != $userId) err('No permessi', 403);

    $sale = $pdo->query("SELECT id_sala, nome, capienza FROM Sala ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    $settori = $pdo->query("SELECT id_settore, nome FROM Settore ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    ok(['booking' => $booking, 'sale' => $sale, 'settori' => $settori]);
}

// POST
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Dati Prenotazione
    $id = (int)($input['id_prenotazione'] ?? 0);
    $attivita = trim($input['attivita'] ?? '');
    $data = $input['data'] ?? '';
    $idSala = (int)($input['id_sala'] ?? 0);
    $oraInizio = (int)($input['ora_inizio'] ?? 0);
    $durata = (int)($input['durata_ore'] ?? 0);

    // Dati Inviti
    $inviteAll = $input['invite_all'] ?? false; // NUOVO FLAG
    $targetRoles = $input['target_roles'] ?? [];
    $targetSectors = $input['target_sectors'] ?? [];

    // ... (Inserire qui i controlli di sicurezza: date, id, conflitti, permessi) ...
    // Esempio rapido:
    $stmtCheck = $pdo->prepare("SELECT id_organizzatore FROM Prenotazione WHERE id_prenotazione = ?");
    $stmtCheck->execute([$id]);
    $existing = $stmtCheck->fetch();
    if(!$existing || ($userRole!=='tecnico' && $existing['id_organizzatore']!=$userId)) err('No permessi');
    // ...

    try {
        $pdo->beginTransaction();

        // 1. UPDATE PRENOTAZIONE
        $upd = $pdo->prepare("UPDATE Prenotazione SET attivita=?, data=?, id_sala=?, ora_inizio=?, durata_ore=? WHERE id_prenotazione=?");
        $upd->execute([$attivita, $data, $idSala, $oraInizio, $durata, $id]);

        // 2. GESTIONE INVITI
        $invitesSent = 0;
        
        // Verifica se l'utente vuole modificare gli inviti
        // Se inviteAll è true OPPURE se ha selezionato delle checkbox
        if ($inviteAll === true || !empty($targetRoles) || !empty($targetSectors)) {
            
            // A. Cancelliamo SEMPRE i vecchi inviti
            $pdo->prepare("DELETE FROM invito WHERE id_prenotazione = ?")->execute([$id]);

            $userIdsToInvite = [];

            // B. SCENARIO 1: INVITA TUTTI
            if ($inviteAll === true) {
                // Query semplice: tutti tranne me
                $stmtAll = $pdo->prepare("SELECT id_iscritto FROM Iscritto WHERE id_iscritto != ?");
                $stmtAll->execute([$userId]);
                $userIdsToInvite = $stmtAll->fetchAll(PDO::FETCH_COLUMN);
            } 
            // C. SCENARIO 2: FILTRI SPECIFICI
            else {
                // Costruzione Query Dinamica (come prima)
                $queryUsers = "
                    SELECT DISTINCT i.id_iscritto 
                    FROM Iscritto i
                    LEFT JOIN afferisce a ON i.id_iscritto = a.id_iscritto
                    WHERE i.id_iscritto != :me
                ";
                $params = [':me' => $userId];

                if (!empty($targetRoles)) {
                    $inRoles = str_repeat('?,', count($targetRoles) - 1) . '?';
                    $queryUsers .= " AND i.ruolo IN ($inRoles)";
                    $params = array_merge($params, $targetRoles);
                }

                if (!empty($targetSectors)) {
                    $inSectors = str_repeat('?,', count($targetSectors) - 1) . '?';
                    $queryUsers .= " AND a.id_settore IN ($inSectors)";
                    $params = array_merge($params, $targetSectors);
                }

                // Esecuzione
                $queryFinal = str_replace(':me', '?', $queryUsers);
                $finalParams = array_merge([$userId], $targetRoles, $targetSectors);
                
                $stmtTarget = $pdo->prepare($queryFinal);
                $stmtTarget->execute($finalParams);
                $userIdsToInvite = $stmtTarget->fetchAll(PDO::FETCH_COLUMN);
            }

            // D. INSERIMENTO INVITI
            if (!empty($userIdsToInvite)) {
                $sqlInv = "INSERT INTO invito (id_iscritto, id_prenotazione, data_invio, stato) VALUES (?, ?, CURDATE(), 'pendente')";
                $stmtInv = $pdo->prepare($sqlInv);
                foreach ($userIdsToInvite as $uid) {
                    $stmtInv->execute([$uid, $id]);
                    $invitesSent++;
                }
            }
        }

        $pdo->commit();
        ok(['message' => 'Modifica salvata', 'invites_sent' => $invitesSent]);

    } catch (Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        err('Errore: ' . $e->getMessage());
    }
}
?>
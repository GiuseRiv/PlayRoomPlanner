<?php declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($d=[]) { echo json_encode(['ok'=>true, 'data'=>$d]); exit; }
function err($m,$c=400) { http_response_code($c); echo json_encode(['ok'=>false, 'message'=>$m]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Metodo non consentito', 405);

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];

$idPrenotazione = (int)($input['id_prenotazione'] ?? 0);
if ($idPrenotazione <= 0) err('ID prenotazione mancante');

// Parametri nuovi
$inviteAll = $input['invite_all'] ?? false;
$targetRoles = $input['target_roles'] ?? [];
$targetSectors = $input['target_sectors'] ?? [];

// Verifica proprietà della prenotazione
$stmt = $pdo->prepare("SELECT id_organizzatore, stato FROM Prenotazione WHERE id_prenotazione = ?");
$stmt->execute([$idPrenotazione]);
$booking = $stmt->fetch();

if (!$booking) err('Prenotazione non trovata');
if ($_SESSION['user_ruolo'] !== 'tecnico' && $booking['id_organizzatore'] != $userId) {
    err('Non sei l\'organizzatore');
}
if ($booking['stato'] !== 'confermata') err('Prenotazione non confermata');


// --- LOGICA SELEZIONE UTENTI (Identica a booking_edit) ---
$userIdsToInvite = [];

if ($inviteAll === true) {
    // 1. Invita Tutti
    $stmtAll = $pdo->prepare("SELECT id_iscritto FROM Iscritto WHERE id_iscritto != ?");
    $stmtAll->execute([$userId]);
    $userIdsToInvite = $stmtAll->fetchAll(PDO::FETCH_COLUMN);
} 
else if (!empty($targetRoles) || !empty($targetSectors)) {
    // 2. Query Dinamica (Intersezione Ruoli/Settori)
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

    // Replace :me with ? for consistent positional binding
    $queryFinal = str_replace(':me', '?', $queryUsers);
    $finalParams = array_merge([$userId], $targetRoles, $targetSectors);

    $stmtTarget = $pdo->prepare($queryFinal);
    $stmtTarget->execute($finalParams);
    $userIdsToInvite = $stmtTarget->fetchAll(PDO::FETCH_COLUMN);
}

// --- INSERIMENTO ---
$count = 0;
if (!empty($userIdsToInvite)) {
    $sqlInsert = "INSERT IGNORE INTO invito (id_iscritto, id_prenotazione, data_invio, stato) VALUES (?, ?, CURDATE(), 'pendente')";
    $stmtInsert = $pdo->prepare($sqlInsert);

    foreach ($userIdsToInvite as $uid) {
        $stmtInsert->execute([$uid, $idPrenotazione]);
        if ($stmtInsert->rowCount() > 0) {
            $count++;
        }
    }
}

ok(['invites_sent' => $count]);
?>
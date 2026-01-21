<?php
declare(strict_types=1);

// --- DEBUG: Decommenta queste righe se hai ancora errore 500 per vedere il messaggio esatto ---
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);
// ---------------------------------------------------------------------------------------------

ob_start(); // Buffer output per gestire json pulito
ini_set('display_errors', '0'); // In produzione nascondiamo errori HTML

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        throw new Exception('Non autenticato', 401);
    }

    $day = $_GET['day'] ?? date('Y-m-d');
    $ts = strtotime($day);
    if ($ts === false) {
        throw new Exception('Parametro day non valido', 400);
    }

    $dow = (int)date('N', $ts);
    $mondayTs = strtotime('-' . ($dow - 1) . ' day', $ts);
    $sundayTs = strtotime('+' . (7 - $dow) . ' day', $ts);
    $monday = date('Y-m-d', $mondayTs);
    $sunday = date('Y-m-d', $sundayTs);

    // SQL CORRETTO:
    // Abbiamo distinto :uid_invito e :uid_organizzatore per evitare crash su PDO
    $sql = "
    SELECT 
        p.id_prenotazione,
        p.id_organizzatore,
        p.data,
        p.ora_inizio,
        p.durata_ore,
        p.attivita,
        s.nome AS nome_sala,
        CONCAT(org.nome, ' ', org.cognome) AS organizzatore,
        
        CASE 
            WHEN p.id_organizzatore = :uid_organizzatore THEN 'organizzatore'
            ELSE i.stato 
        END AS stato_invito

    FROM Prenotazione p
    JOIN Sala s ON s.id_sala = p.id_sala
    JOIN Iscritto org ON org.id_iscritto = p.id_organizzatore
    -- Usiamo :uid_invito qui
    LEFT JOIN invito i ON p.id_prenotazione = i.id_prenotazione AND i.id_iscritto = :uid_invito

    WHERE 
        p.data BETWEEN :monday AND :sunday
        AND p.stato = 'confermata'
        AND (
            p.id_organizzatore = :uid_organizzatore2  -- Usiamo un nome univoco o ripetiamo il binding
            OR 
            i.id_iscritto IS NOT NULL
        )

    ORDER BY p.data ASC, p.ora_inizio ASC
    ";

    $stmt = $pdo->prepare($sql);
    
    // Bind esplicito dei parametri
    $stmt->execute([
        'uid_invito'        => $userId,
        'uid_organizzatore' => $userId,
        'uid_organizzatore2'=> $userId, // Alcuni driver richiedono nomi unici per ogni occorrenza
        'monday'            => $monday,
        'sunday'            => $sunday
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Post-Processing ---
    foreach ($rows as &$r) {
        // Costruiamo la data ora completa
        $dtInizio = new DateTime($r['data'] . ' ' . sprintf('%02d:00:00', $r['ora_inizio']));
        $now = new DateTime();
        
        $statoInvito = $r['stato_invito'];
        
        // Se l'evento è passato ed era pendente, lo segniamo eseguito
        if ($dtInizio < $now && $statoInvito === 'pendente') {
            $statoInvito = 'eseguito';
        }
        
        $r['stato_effettivo'] = $statoInvito;
    }
    unset($r);

    $out = [];
    $rejected = [];

    foreach ($rows as $r) {
        $isOrganizer = ((int)$r['id_organizzatore'] === $userId);

        $row = [
            'id_prenotazione' => (int)$r['id_prenotazione'],
            'data'            => $r['data'],
            'ora'             => str_pad((string)((int)$r['ora_inizio']), 2, '0', STR_PAD_LEFT) . ':00',
            'durata'          => ((int)$r['durata_ore']) . 'h',
            'sala'            => $r['nome_sala'],
            'attivita'        => $r['attivita'] ?? '',
            'organizzatore'   => $r['organizzatore'],
            'stato_invito'    => $r['stato_effettivo'],
            'can_cancel'      => $isOrganizer,
        ];

        if ($r['stato_invito'] === 'rifiutato') {
            $rejected[] = $row;
        } else {
            $out[] = $row;
        }
    }

    ob_clean();
    echo json_encode([
        'ok' => true,
        'data' => $out,
        'week' => ['monday' => $monday, 'sunday' => $sunday],
        'rejected' => $rejected
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    ob_clean();
    echo json_encode([
        'ok' => false, 
        'message' => 'Errore server: ' . $e->getMessage()
    ]);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    ob_clean();
    echo json_encode([
        'ok' => false, 
        'message' => 'Errore Database: ' . $e->getMessage()
    ]);
    exit;
}
?>
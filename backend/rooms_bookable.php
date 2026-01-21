<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

function ok($data=null, int $code=200): void {
    http_response_code($code);
    echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE);
    exit;
}
function err(string $m, int $code=400): void {
    http_response_code($code);
    echo json_encode(['ok'=>false,'message'=>$m], JSON_UNESCAPED_UNICODE);
    exit;
}

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) err('Non autenticato', 401);

$ruolo = (string)($_SESSION['user_ruolo'] ?? '');

try {
    $rooms = [];

    // --- 1. RECUPERO SALE (Filtrate per permessi) ---
    if ($ruolo === 'tecnico') {
        // Tecnico: tutte le sale
        $st = $pdo->query("
            SELECT 
                s.id_sala, 
                s.nome AS nome_sala, 
                s.capienza, 
                se.id_settore, 
                se.nome AS nome_settore, 
                se.tipo AS tipo_settore
            FROM Sala s
            JOIN Settore se ON se.id_settore = s.id_settore
            ORDER BY se.nome ASC, s.nome ASC
        ");
        $rooms = $st->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Non tecnico: solo sale del proprio settore o tipo
        $sql = "
            SELECT DISTINCT 
                s.id_sala, 
                s.nome AS nome_sala, 
                s.capienza, 
                seSala.id_settore, 
                seSala.nome AS nome_settore, 
                seSala.tipo AS tipo_settore
            FROM Sala s
            JOIN Settore seSala ON seSala.id_settore = s.id_settore
            WHERE 
                seSala.id_responsabile = :uid
                OR seSala.tipo IN (
                    SELECT seMio.tipo 
                    FROM Settore seMio 
                    WHERE seMio.id_responsabile = :uid2
                )
            ORDER BY seSala.nome ASC, s.nome ASC
        ";
        $st = $pdo->prepare($sql);
        $st->execute(['uid' => $uid, 'uid2' => $uid]);
        $rooms = $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- 2. RECUPERO SETTORI (Globale per inviti) ---
    // Recuperiamo l'elenco completo dei settori per popolare il menu "Invita per settore"
    // Questo permette a un musicista di invitare gente del teatro, se lo desidera.
    $stSec = $pdo->query("SELECT id_settore, nome FROM Settore ORDER BY nome ASC");
    $sectors = $stSec->fetchAll(PDO::FETCH_ASSOC);

    // --- 3. OUTPUT COMBINATO ---
    ok([
        'rooms' => $rooms,
        'sectors' => $sectors
    ]);

} catch (Throwable $e) {
    err('Errore server: ' . $e->getMessage(), 500);
}
?>
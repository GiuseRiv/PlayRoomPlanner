<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m], JSON_UNESCAPED_UNICODE); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) err('Non autenticato', 401);

function is_tecnico(): bool {
    return ((string)($_SESSION['user_ruolo'] ?? '')) === 'tecnico';
}

/**
 * Permessi:
 * - Tecnico: può prenotare qualunque sala
 * - Responsabile: deve essere responsabile di un settore
 */
function require_can_manage_bookings(PDO $pdo, int $uid): void {
    if (is_tecnico()) return;

    $st = $pdo->prepare("SELECT 1 FROM Settore WHERE id_responsabile=? LIMIT 1");
    $st->execute([$uid]);
    if (!$st->fetchColumn()) {
        err('Operazione consentita solo ai responsabili di settore o ai tecnici', 403);
    }
}

function validate_slot(int $ora, int $dur): void {
    if ($ora < 9 || $ora > 23) err('ora_inizio deve essere tra 9 e 23', 422);
    if ($dur <= 0) err('durata_ore non valida', 422);
    if ($ora + $dur > 23) err('La prenotazione deve terminare entro le 23', 422);
}

function validate_not_in_past(string $date, int $ora): void {
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if (!$d) err('Data non valida (formato atteso YYYY-MM-DD)', 422);

    $today = new DateTimeImmutable('today');
    if ($d < $today) {
        err('Non puoi prenotare nel passato', 422);
    }

    if ($d->format('Y-m-d') === $today->format('Y-m-d')) {
        $now = new DateTimeImmutable('now');
        $nextHour = ((int)$now->format('G')) + 1; // prossima ora intera
        $minHour = max(9, min(23, $nextHour));
        if ($ora < $minHour) {
            err('Per oggi puoi prenotare solo a partire dalla prossima ora', 422);
        }
    }
}

function can_book_room(PDO $pdo, int $uid, int $idSala): bool {
    if (is_tecnico()) return true;

    $sql = "
        SELECT 1
        FROM Sala s
        JOIN Settore seSala ON seSala.id_settore = s.id_settore
        WHERE s.id_sala = :idSala
          AND (
            seSala.id_responsabile = :uid1
            OR seSala.tipo IN (
              SELECT seMio.tipo
              FROM Settore seMio
              WHERE seMio.id_responsabile = :uid2
            )
          )
        LIMIT 1
    ";
    $st = $pdo->prepare($sql);
    $st->execute(['idSala' => $idSala, 'uid1' => $uid, 'uid2' => $uid]);
    return (bool)$st->fetchColumn();
}

/**
 * Verifica se la SALA è occupata da altri eventi.
 * Usa logica overlap stretta: (StartA < EndB) AND (EndA > StartB)
 */
function check_room_overlap(PDO $pdo, int $idSala, string $data, int $oraInizio, int $durata, ?int $excludeId = null): void {
    $oraFine = $oraInizio + $durata;
    $hasExclude = ($excludeId !== null);

    $sql = "
        SELECT 1
        FROM Prenotazione p
        WHERE p.id_sala = :idSala
          AND p.data = :data
          AND p.stato = 'confermata'
          " . ($hasExclude ? "AND p.id_prenotazione <> :excludeId" : "") . "
          AND (p.ora_inizio < :oraFine AND (p.ora_inizio + p.durata_ore) > :oraInizio)
        LIMIT 1
    ";

    $params = [
        'idSala'    => $idSala,
        'data'      => $data,
        'oraInizio' => $oraInizio,
        'oraFine'   => $oraFine
    ];
    if ($hasExclude) $params['excludeId'] = $excludeId;

    $st = $pdo->prepare($sql);
    $st->execute($params);

    if ($st->fetch()) err('La sala selezionata è già occupata in questo orario.', 409);
}

/**
 * Verifica se l'ORGANIZZATORE è impegnato in altri eventi (come organizzatore o invitato).
 * Usa logica overlap stretta.
 */
function check_organizer_overlap(PDO $pdo, int $uid, string $data, int $oraInizio, int $durata, ?int $excludeId = null): void {
    $oraFine = $oraInizio + $durata;
    $hasExclude = ($excludeId !== null);

    $sql = "
        SELECT 1
        FROM Prenotazione p
        LEFT JOIN invito i ON p.id_prenotazione = i.id_prenotazione AND i.id_iscritto = :uidInvito
        WHERE p.data = :data
          AND p.stato = 'confermata'
          " . ($hasExclude ? "AND p.id_prenotazione <> :excludeId" : "") . "
          -- L'utente è coinvolto se è l'organizzatore OPPURE se è un invitato (non rifiutato)
          AND (
              p.id_organizzatore = :uidOrg 
              OR 
              (i.id_iscritto IS NOT NULL AND i.stato != 'rifiutato')
          )
          -- Verifica sovrapposizione oraria stretta
          AND (p.ora_inizio < :oraFine AND (p.ora_inizio + p.durata_ore) > :oraInizio)
        LIMIT 1
    ";

    $params = [
        'uidInvito' => $uid,
        'uidOrg'    => $uid,
        'data'      => $data,
        'oraInizio' => $oraInizio,
        'oraFine'   => $oraFine
    ];
    if ($hasExclude) $params['excludeId'] = $excludeId;

    $st = $pdo->prepare($sql);
    $st->execute($params);

    if ($st->fetch()) err("Sei già impegnato in un'altra attività (come organizzatore o partecipante) in questo orario.", 409);
}

try {
    if ($method === 'GET') {
        if ($id > 0) {
            $st = $pdo->prepare("
                SELECT p.*, s.nome AS nome_sala
                FROM Prenotazione p
                JOIN Sala s ON s.id_sala = p.id_sala
                WHERE p.id_prenotazione = ?
            ");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) err('Prenotazione non trovata', 404);
            
            // Verifica permessi visualizzazione (opzionale, ma buona prassi)
            // Se tecnico vede tutto, altrimenti solo le proprie? 
            // Per ora lasciamo invariato come da tua logica originale
            ok($row);
        } else {
            // Ritorna le prenotazioni dell'utente (come organizzatore)
            $st = $pdo->prepare("
                SELECT p.*, s.nome AS nome_sala
                FROM Prenotazione p
                JOIN Sala s ON s.id_sala = p.id_sala
                WHERE p.id_organizzatore = ?
                ORDER BY p.data ASC, p.ora_inizio ASC
            ");
            $st->execute([$uid]);
            ok($st->fetchAll(PDO::FETCH_ASSOC));
        }
    }

    if ($method === 'POST') {
        require_can_manage_bookings($pdo, $uid);

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) err('JSON non valido', 422);

        $idSala = (int)($input['id_sala'] ?? 0);
        $date = trim((string)($input['data'] ?? ''));
        $ora = (int)($input['ora_inizio'] ?? 0);
        $dur = (int)($input['durata_ore'] ?? 0);
        $attivita = trim((string)($input['attivita'] ?? ''));

        if ($idSala <= 0 || $date === '') err('Parametri mancanti', 422);

        if (!can_book_room($pdo, $uid, $idSala)) {
            err('Non puoi prenotare questa sala (settore non autorizzato)', 403);
        }

        validate_slot($ora, $dur);
        validate_not_in_past($date, $ora);

        // 1. Controllo Sala Occupata
        check_room_overlap($pdo, $idSala, $date, $ora, $dur, null);

        // 2. Controllo Organizzatore Impegnato
        check_organizer_overlap($pdo, $uid, $date, $ora, $dur, null);

        $st = $pdo->prepare("
            INSERT INTO Prenotazione (data, ora_inizio, durata_ore, attivita, stato, id_sala, id_organizzatore)
            VALUES (?, ?, ?, ?, 'confermata', ?, ?)
        ");
        $st->execute([$date, $ora, $dur, $attivita, $idSala, $uid]);

        ok(['id_prenotazione' => (int)$pdo->lastInsertId()], 201);
    }

    if ($method === 'PUT') {
        require_can_manage_bookings($pdo, $uid);
        if ($id <= 0) err('id mancante', 422);

        // Recupera dati attuali
        $stmt = $pdo->prepare("SELECT * FROM Prenotazione WHERE id_prenotazione=?");
        $stmt->execute([$id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) err('Prenotazione non trovata', 404);

        // Verifica permessi modifica
        if ((int)$p['id_organizzatore'] !== $uid) err('Non autorizzato', 403);

        // Verifica se evento passato
        $dtVecchia = new DateTime($p['data'].' '.sprintf('%02d:00:00',$p['ora_inizio']));
        if ($dtVecchia < new DateTime()) {
            err('⏰ Evento passato: non più modificabile', 410);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) err('JSON non valido', 422);

        $idSala   = (int)($input['id_sala'] ?? $p['id_sala']);
        $date     = trim((string)($input['data'] ?? $p['data']));
        $ora      = (int)($input['ora_inizio'] ?? $p['ora_inizio']);
        $dur      = (int)($input['durata_ore'] ?? $p['durata_ore']);
        $attivita = trim((string)($input['attivita'] ?? $p['attivita']));
        $stato    = (string)($input['stato'] ?? $p['stato']); // Opzionale, gestito se inviato

        if (!can_book_room($pdo, $uid, $idSala)) {
            err('Non puoi prenotare questa sala (settore non autorizzato)', 403);
        }

        validate_slot($ora, $dur);
        validate_not_in_past($date, $ora);

        // 1. Controllo Sala Occupata (escludendo se stessa)
        check_room_overlap($pdo, $idSala, $date, $ora, $dur, $id);

        // 2. Controllo Organizzatore Impegnato (escludendo se stessa)
        check_organizer_overlap($pdo, $uid, $date, $ora, $dur, $id);

        $st = $pdo->prepare("
            UPDATE Prenotazione
            SET data=?, ora_inizio=?, durata_ore=?, attivita=?, stato=?, id_sala=?
            WHERE id_prenotazione=?
        ");
        $st->execute([$date, $ora, $dur, $attivita, $stato, $idSala, $id]);

        ok(['message' => 'Prenotazione aggiornata con successo']);
    }

    if ($method === 'DELETE') {
        require_can_manage_bookings($pdo, $uid);
        if ($id <= 0) err('id mancante', 422);

        $stmt = $pdo->prepare("
            SELECT p.id_organizzatore, p.data, p.ora_inizio
            FROM Prenotazione p 
            WHERE p.id_prenotazione = ?
        ");
        $stmt->execute([$id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) err('Prenotazione non trovata', 404);

        if ((int)$p['id_organizzatore'] !== $uid) err('Non autorizzato', 403);

        $dtInizio = new DateTime($p['data'] . ' ' . sprintf('%02d:00:00', $p['ora_inizio']));
        if ($dtInizio < new DateTime()) {
            err('Impegno scaduto: non più annullabile', 410);
        }

        $stmt = $pdo->prepare("UPDATE Prenotazione SET stato='annullata' WHERE id_prenotazione=?");
        $stmt->execute([$id]);

        ok(['message' => 'Prenotazione annullata con successo']);
    }

    err('Metodo non supportato', 405);

} catch (Throwable $e) {
    err('Errore server: ' . $e->getMessage() . ' (linea ' . $e->getLine() . ')', 500);
}
?>
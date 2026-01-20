<?php declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

ob_clean(); 
header('Content-Type: application/json; charset=utf-8');

function ok($data){ echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$ruoloSessione = $_SESSION['user_ruolo'] ?? '';

// =================================================================================
// 1. METODO GET
// =================================================================================
if ($method === 'GET') {
    // Permettiamo l'accesso sia a tecnici che a docenti (solo lettura lista)
    if (!in_array($ruoloSessione, ['tecnico', 'docente'])) {
        err('Accesso negato: privilegi insufficienti', 403);
    }

    // CASO A: Richiesta info settori (per le select box)
    if (isset($_GET['info_settori'])) {
        try {
            $sql = "SELECT s.id_settore, s.nome, s.id_responsabile, 
                           CONCAT(i.nome, ' ', i.cognome) as nome_responsabile
                    FROM Settore s
                    LEFT JOIN Iscritto i ON s.id_responsabile = i.id_iscritto
                    ORDER BY s.nome ASC";
            $stmt = $pdo->query($sql);
            ok($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            err('Errore settori: ' . $e->getMessage());
        }
    }

    // CASO B: Richiesta SINGOLO utente (Modifica Profilo)
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = (int)$_GET['id'];
        
        try {
            $sql = "
              SELECT u.id_iscritto, u.nome, u.cognome, u.ruolo, u.email, u.data_nascita, u.foto,
                     GROUP_CONCAT(DISTINCT s.nome SEPARATOR ', ') AS settori_nomi,
                     GROUP_CONCAT(DISTINCT s.id_settore) AS settori_ids
              FROM Iscritto u 
              LEFT JOIN afferisce a ON a.id_iscritto = u.id_iscritto
              LEFT JOIN Settore s ON s.id_settore = a.id_settore
              WHERE u.id_iscritto = ?
              GROUP BY u.id_iscritto
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$user) err('Utente non trovato', 404);
    
            // Info Responsabile
            $stmtResp = $pdo->prepare("SELECT id_settore, data_nomina FROM Settore WHERE id_responsabile = ?");
            $stmtResp->execute([$id]);
            $respData = $stmtResp->fetch(PDO::FETCH_ASSOC);
    
            if ($respData) {
                $user['is_responsabile'] = true;
                $user['responsabile_id_settore'] = $respData['id_settore'];
                $user['data_nomina'] = $respData['data_nomina'];
            } else {
                $user['is_responsabile'] = false;
                $user['responsabile_id_settore'] = null;
                $user['data_nomina'] = null;
            }
    
            ok($user);
        } catch (Exception $e) {
            err('Errore caricamento utente: ' . $e->getMessage());
        }
    }

    // CASO C: Richiesta LISTA utenti (Gestione Iscritti)
    // Se non c'è ID, assumiamo sia la lista
    try {
        $roleFilter = $_GET['role'] ?? '';
        $search = trim($_GET['search'] ?? '');
        $sector = (int)($_GET['sector'] ?? 0);

        $sql = "
          SELECT 
            u.id_iscritto, u.nome, u.cognome, u.ruolo, u.email, u.foto,
            GROUP_CONCAT(DISTINCT s.nome SEPARATOR ', ') AS settori
          FROM Iscritto u 
          LEFT JOIN afferisce a ON a.id_iscritto = u.id_iscritto
          LEFT JOIN Settore s ON s.id_settore = a.id_settore
        ";

        $params = [];
        $where = [];

        if ($roleFilter) { $where[] = 'u.ruolo = ?'; $params[] = $roleFilter; }
        if ($search) { 
          $where[] = "(u.nome LIKE ? OR u.cognome LIKE ? OR u.email LIKE ?)"; 
          $s = "%$search%";
          $params[] = $s; $params[] = $s; $params[] = $s; 
        }
        if ($sector) { $where[] = 'a.id_settore = ?'; $params[] = $sector; }

        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        
        $sql .= ' GROUP BY u.id_iscritto ORDER BY u.cognome, u.nome';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        ok($stmt->fetchAll(PDO::FETCH_ASSOC));

    } catch (Exception $e) {
        err('Errore lista utenti: ' . $e->getMessage());
    }
}

// =================================================================================
// 2. METODO PUT: Salvataggio Modifiche (LOGICA AVANZATA)
// =================================================================================
if ($method === 'PUT') {
    // ... (Tutto il codice PUT che abbiamo sistemato nel passaggio precedente) ...
    // Lo ricopio qui per completezza del file unico
    
    if ($ruoloSessione !== 'tecnico') err('Solo i tecnici possono modificare gli utenti', 403);

    $id = (int)($_GET['id'] ?? 0);
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($id <= 0) err('ID utente mancante');

    $nuovoRuolo = $input['ruolo'] ?? '';
    $nuoviSettoriIds = $input['settori'] ?? []; 
    $diventaResponsabile = $input['is_responsabile'] ?? false;
    $idSettoreResp = (int)($input['id_settore_responsabilita'] ?? 0);
    $dataOggi = date('Y-m-d'); 

    if (!in_array($nuovoRuolo, ['allievo', 'docente', 'tecnico'])) err('Ruolo non valido');

    try {
        $pdo->beginTransaction();

        // 1. Aggiorna Ruolo
        $pdo->prepare("UPDATE Iscritto SET ruolo = ? WHERE id_iscritto = ?")->execute([$nuovoRuolo, $id]);

        // 2. Aggiorna Competenze
        $pdo->prepare("DELETE FROM afferisce WHERE id_iscritto = ?")->execute([$id]);
        
        $idsDaIns = [];
        if ($nuovoRuolo === 'tecnico') {
            $idsDaIns = $pdo->query("SELECT id_settore FROM Settore")->fetchAll(PDO::FETCH_COLUMN);
            $diventaResponsabile = false; 
        } else {
            $idsDaIns = $nuoviSettoriIds;
        }

        if ($diventaResponsabile && $idSettoreResp > 0 && !in_array($idSettoreResp, $idsDaIns)) {
            $idsDaIns[] = $idSettoreResp;
        }

        if (!empty($idsDaIns)) {
            $ins = $pdo->prepare("INSERT INTO afferisce (id_iscritto, id_settore) VALUES (?, ?)");
            foreach ($idsDaIns as $sid) $ins->execute([$id, $sid]);
        }

        // 3. Gestione Responsabilità (Logica Mono-Settore + Vacante)
        
        // A. Rimuovi utente da qualsiasi responsabilità precedente
        $pdo->prepare("UPDATE Settore SET id_responsabile = NULL, data_nomina = NULL WHERE id_responsabile = ?")
            ->execute([$id]);

        // B. Assegna nuova responsabilità (se valida)
        if ($nuovoRuolo === 'docente' && $diventaResponsabile && $idSettoreResp) {
            // Svuota il nuovo settore se era occupato da qualcun altro
            $pdo->prepare("UPDATE Settore SET id_responsabile = NULL, data_nomina = NULL WHERE id_settore = ?")
                ->execute([$idSettoreResp]);

            // Assegna
            $pdo->prepare("UPDATE Settore SET id_responsabile = ?, data_nomina = ? WHERE id_settore = ?")
                ->execute([$id, $dataOggi, $idSettoreResp]);
        }

        $pdo->commit();
        ok(['message' => 'Utente aggiornato con successo']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        err('Errore aggiornamento: ' . $e->getMessage());
    }
}

// =================================================================================
// 3. METODO DELETE: Eliminazione Utente
// =================================================================================
if ($method === 'DELETE') {
    if ($ruoloSessione !== 'tecnico') err('Solo i tecnici possono eliminare utenti', 403);
    
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) err('ID mancante');

    // Prevenire auto-eliminazione
    if ($id == $_SESSION['user_id']) err('Non puoi eliminare te stesso');

    try {
        $pdo->beginTransaction();
        
        // La cancellazione a cascata dovrebbe gestire afferisce/prenotazioni se configurata,
        // ma per sicurezza rimuoviamo i legami manuali critici.
        
        // 1. Rimuovi responsabilità settori (lascia vacante)
        $pdo->prepare("UPDATE Settore SET id_responsabile = NULL WHERE id_responsabile = ?")->execute([$id]);
        
        // 2. Elimina utente (Afferisce e altro vanno via in CASCADE se impostato nel DB, 
        // altrimenti aggiungi qui le delete manuali)
        $stmt = $pdo->prepare("DELETE FROM Iscritto WHERE id_iscritto = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        ok(['message' => 'Utente eliminato']);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        err('Errore eliminazione: ' . $e->getMessage());
    }
}

// Metodo non supportato
err('Metodo non supportato', 405);
?>
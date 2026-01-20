<?php declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

ob_clean(); 
header('Content-Type: application/json; charset=utf-8');

function ok($data){ echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err($m,$c=400){ http_response_code($c); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$ruoloSessione = $_SESSION['user_ruolo'] ?? '';


if ($method === 'GET') {
    if (!in_array($ruoloSessione, ['tecnico', 'docente'])) err('Accesso negato', 403);

    
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

    
    if (!isset($_GET['id'])) {
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
            err('Errore lista: ' . $e->getMessage());
        }
    }

    
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

        
        $stmtResp = $pdo->prepare("SELECT id_settore, data_nomina, anni_servizio FROM Settore WHERE id_responsabile = ?");
        $stmtResp->execute([$id]);
        $respData = $stmtResp->fetch(PDO::FETCH_ASSOC);

        if ($respData) {
            $user['is_responsabile'] = true;
            $user['responsabile_id_settore'] = $respData['id_settore'];
            $user['data_nomina'] = $respData['data_nomina'];
            $user['anni_servizio'] = $respData['anni_servizio'];
        } else {
            $user['is_responsabile'] = false;
            $user['responsabile_id_settore'] = null;
            $user['data_nomina'] = null;
            $user['anni_servizio'] = 0;
        }

        ok($user);
    } catch (Exception $e) {
        err('Errore utente: ' . $e->getMessage());
    }
}


if ($method === 'PUT') {
    if ($ruoloSessione !== 'tecnico') err('Solo i tecnici possono modificare', 403);

    $id = (int)($_GET['id'] ?? 0);
    $input = json_decode(file_get_contents('php://input'), true);
    
    $nuovoRuolo = $input['ruolo'] ?? '';
    $nuoviSettoriIds = $input['settori'] ?? []; 
    $diventaResponsabile = $input['is_responsabile'] ?? false;
    $idSettoreResp = (int)($input['id_settore_responsabilita'] ?? 0);
    $dataOggi = date('Y-m-d'); 

    if (!in_array($nuovoRuolo, ['allievo', 'docente', 'tecnico'])) err('Ruolo non valido');

    try {
        $pdo->beginTransaction();

        
        $pdo->prepare("UPDATE Iscritto SET ruolo = ? WHERE id_iscritto = ?")->execute([$nuovoRuolo, $id]);
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

        
        $pdo->prepare("UPDATE Settore SET id_responsabile = NULL, data_nomina = NULL WHERE id_responsabile = ?")
            ->execute([$id]);

        
        if ($nuovoRuolo === 'docente' && $diventaResponsabile && $idSettoreResp) {
            
            $pdo->prepare("UPDATE Settore SET id_responsabile = NULL, data_nomina = NULL WHERE id_settore = ?")
                ->execute([$idSettoreResp]);

            
            $pdo->prepare("UPDATE Settore SET id_responsabile = ?, data_nomina = ?, anni_servizio = 0 WHERE id_settore = ?")
                ->execute([$id, $dataOggi, $idSettoreResp]);
        }

        $pdo->commit();
        ok(['message' => 'Salvato con successo']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        err($e->getMessage());
    }
}


if ($method === 'DELETE') {
    if ($ruoloSessione !== 'tecnico') err('Solo i tecnici possono eliminare', 403);
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0 || $id == $_SESSION['user_id']) err('ID non valido');

    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE Settore SET id_responsabile = NULL WHERE id_responsabile = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM Iscritto WHERE id_iscritto = ?")->execute([$id]);
        $pdo->commit();
        ok(['message' => 'Eliminato']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        err($e->getMessage());
    }
}
?>
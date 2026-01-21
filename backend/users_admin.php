<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$uid = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_ruolo'] ?? '';

if (!in_array($userRole, ['tecnico', 'docente'])) {
    err('Accesso negato', 403);
}

try {
    if ($method === 'GET') {
        
        if (isset($_GET['info_settori'])) {
            $sql = "SELECT s.id_settore, s.nome, s.id_responsabile, 
                           CONCAT(i.nome, ' ', i.cognome) as nome_responsabile
                    FROM Settore s
                    LEFT JOIN Iscritto i ON s.id_responsabile = i.id_iscritto
                    ORDER BY s.nome ASC";
            $stmt = $pdo->query($sql);
            ok($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            
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
        }

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
    }

    if ($method === 'PUT') {
        if ($userRole !== 'tecnico') err('Solo i tecnici possono modificare', 403);

        $id = (int)($_GET['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($id <= 0) err('ID mancante');

        $nuovoRuolo = $input['ruolo'] ?? '';
        $nuoviSettoriIds = $input['settori'] ?? [];
        $attivaNomina = $input['is_responsabile'] ?? false; 
        $targetSettoreResp = (int)($input['id_settore_responsabilita'] ?? 0);
        
        if (!in_array($nuovoRuolo, ['allievo', 'docente', 'tecnico'])) err('Ruolo non valido');

        try {
            $pdo->beginTransaction();

            $stmtSnap = $pdo->prepare("SELECT id_settore FROM Settore WHERE id_responsabile = ?");
            $stmtSnap->execute([$id]);
            $currentRespId = (int)$stmtSnap->fetchColumn(); 

            $pdo->prepare("UPDATE Iscritto SET ruolo = ? WHERE id_iscritto = ?")->execute([$nuovoRuolo, $id]);
            
            // Logica Responsabilità
            if ($nuovoRuolo !== 'docente') {
                if ($currentRespId > 0) {
                    $pdo->prepare("UPDATE Settore SET id_responsabile = NULL, data_nomina = NULL, anni_servizio = 0 WHERE id_settore = ?")
                        ->execute([$currentRespId]);
                }
                $currentRespId = 0; 
            } else {
                if ($attivaNomina === true && $targetSettoreResp > 0) {
                    if ($targetSettoreResp !== $currentRespId) {
                        if ($currentRespId > 0) {
                            $pdo->prepare("UPDATE Settore SET id_responsabile = NULL, data_nomina = NULL, anni_servizio = 0 WHERE id_settore = ?")
                                ->execute([$currentRespId]);
                        }
                        $pdo->prepare("UPDATE Settore SET id_responsabile = NULL, data_nomina = NULL, anni_servizio = 0 WHERE id_settore = ?")
                            ->execute([$targetSettoreResp]);

                        $dataOggi = date('Y-m-d');
                        $pdo->prepare("UPDATE Settore SET id_responsabile = ?, data_nomina = ?, anni_servizio = 0 WHERE id_settore = ?")
                            ->execute([$id, $dataOggi, $targetSettoreResp]);
                        
                        $currentRespId = $targetSettoreResp; 
                    }
                }
            }

            // Logica Competenze (Afferisce)
            $pdo->prepare("DELETE FROM afferisce WHERE id_iscritto = ?")->execute([$id]);
            
            $idsDaIns = [];
            if ($nuovoRuolo === 'tecnico') {
                $idsDaIns = $pdo->query("SELECT id_settore FROM Settore")->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $idsDaIns = array_map('intval', (array)$nuoviSettoriIds);
            }

            if ($currentRespId > 0 && !in_array($currentRespId, $idsDaIns)) {
                $idsDaIns[] = $currentRespId;
            }

            if (!empty($idsDaIns)) {
                $ins = $pdo->prepare("INSERT IGNORE INTO afferisce (id_iscritto, id_settore) VALUES (?, ?)");
                foreach ($idsDaIns as $sid) {
                    if($sid > 0) $ins->execute([$id, $sid]);
                }
            }

            $pdo->commit();
            ok(['message' => 'Utente aggiornato con successo']);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            err('Errore aggiornamento: ' . $e->getMessage());
        }
    }

    if ($method === 'DELETE') {
        if ($userRole !== 'tecnico') err('Solo i tecnici possono eliminare', 403);
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
        }

        if ($id <= 0) err('ID non valido');
        if ($id === $uid) err('Non puoi auto-eliminarti');

        try {
            $pdo->beginTransaction();

            $pdo->prepare("UPDATE Settore SET id_responsabile = NULL, data_nomina = NULL, anni_servizio = 0 WHERE id_responsabile = ?")
                ->execute([$id]);

            $pdo->prepare("DELETE FROM afferisce WHERE id_iscritto = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM invito WHERE id_iscritto = ?")->execute([$id]);
            
            $pdo->prepare("
                DELETE FROM invito 
                WHERE id_prenotazione IN (SELECT id_prenotazione FROM Prenotazione WHERE id_organizzatore = ?)
            ")->execute([$id]);

            $pdo->prepare("DELETE FROM Prenotazione WHERE id_organizzatore = ?")->execute([$id]);

            $stmt = $pdo->prepare("DELETE FROM Iscritto WHERE id_iscritto = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack(); 
                err('Utente non trovato o già eliminato', 404);
            }

            $pdo->commit();
            ok(['message' => 'Utente e tutti i dati collegati eliminati definitivamente.']);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            err('Errore Server: ' . $e->getMessage());
        }
    }

} catch (Exception $e) {
    err('Errore Generale: ' . $e->getMessage(), 500);
}
?>
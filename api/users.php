<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$uid = (int)($_SESSION['user_id'] ?? 0);

try {
  if ($method === 'GET') {
    if ($id > 0) {
      // 1. Recupera dati utente (data_nascita e id_iscritto sono già qui, ottimo!)
      $st = $pdo->prepare("SELECT id_iscritto, nome, cognome, ruolo, email, foto, data_nascita FROM Iscritto WHERE id_iscritto=?");
      $st->execute([$id]);
      $u = $st->fetch(PDO::FETCH_ASSOC);
      
      if (!$u) err('Utente non trovato', 404);

      // 2. Recupera i settori di competenza
      $st2 = $pdo->prepare("SELECT id_settore FROM afferisce WHERE id_iscritto=?");
      $st2->execute([$id]);
      $u['settori_ids'] = $st2->fetchAll(PDO::FETCH_COLUMN);

      ok($u);
    } else {
      $st = $pdo->query("SELECT id_iscritto, nome, cognome, ruolo, email, foto FROM Iscritto");
      ok($st->fetchAll(PDO::FETCH_ASSOC));
    }
  }

  if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) err('JSON non valido', 422);

    $nome = trim((string)($data['nome'] ?? ''));
    $cognome = trim((string)($data['cognome'] ?? ''));
    $email = trim((string)($data['email'] ?? ''));
    $ruolo = (string)($data['ruolo'] ?? 'allievo');
    $data_nascita = (string)($data['data_nascita'] ?? '');
    $password = (string)($data['password'] ?? '');

    if ($nome===''||$cognome===''||$email===''||$data_nascita===''||$password==='') err('Parametri mancanti', 422);

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $st = $pdo->prepare("
      INSERT INTO Iscritto (nome, cognome, email, password, ruolo, data_nascita)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $st->execute([$nome, $cognome, $email, $hash, $ruolo, $data_nascita]);

    ok(['id_iscritto' => (int)$pdo->lastInsertId()], 201);
  }

  if ($method === 'PUT') {
    if ($id <= 0) err('id mancante', 422);
    if ($id !== $uid) err('Puoi modificare solo il tuo profilo', 403);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) err('JSON non valido', 422);

    // ============================================================
    // NUOVO BLOCCO: LOGICA CAMBIO PASSWORD (CON VALIDAZIONE RIGIDA)
    // ============================================================
    if (isset($data['old_password']) && isset($data['new_password'])) {
        
        $newPass = $data['new_password'];
        
        // 1. Recupero la password hashata attuale dal DB
        $q = $pdo->prepare("SELECT password FROM Iscritto WHERE id_iscritto = ?");
        $q->execute([$id]);
        $currentHash = $q->fetchColumn();

        // 2. Verifico se la vecchia password inserita corrisponde all'hash
        if (!$currentHash || !password_verify($data['old_password'], $currentHash)) {
            err('La password attuale non è corretta', 401);
        }

        // 3. Validazione REQUISITI COMPLESSITÀ (Uguale alla registrazione)
        $hasUpper = preg_match('@[A-Z]@', $newPass);
        $hasSpecial = preg_match('@[^\w]@', $newPass); // Carattere non alfanumerico

        if (strlen($newPass) < 8 || !$hasUpper || !$hasSpecial) {
            err('La nuova password deve essere di almeno 8 caratteri, contenere una maiuscola e un carattere speciale.', 400);
        }

        // 4. Aggiorno la password nel DB
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE Iscritto SET password = ? WHERE id_iscritto = ?");
        $upd->execute([$newHash, $id]);

        ok(['message' => 'Password aggiornata con successo']);
    }
    // ============================================================


    // --- QUI INIZIA LA TUA VECCHIA LOGICA UPDATE ANAGRAFICA ---
    $nome = trim((string)($data['nome'] ?? ''));
    $cognome = trim((string)($data['cognome'] ?? ''));
    $ruolo = (string)($data['ruolo'] ?? '');
    $data_nascita = (string)($data['data_nascita'] ?? '');
    $foto = (string)($data['foto'] ?? '');

    $fields = [];
    $params = [];

    if ($nome !== '') { $fields[] = "nome=?"; $params[] = $nome; }
    if ($cognome !== '') { $fields[] = "cognome=?"; $params[] = $cognome; }
    if ($ruolo !== '') { $fields[] = "ruolo=?"; $params[] = $ruolo; }
    if ($data_nascita !== '') { $fields[] = "data_nascita=?"; $params[] = $data_nascita; }
    if ($foto !== '') { $fields[] = "foto=?"; $params[] = $foto; }

    if (!$fields) err('Nessun campo da aggiornare', 422);

    $params[] = $id;
    $sql = "UPDATE Iscritto SET " . implode(', ', $fields) . " WHERE id_iscritto=?";
    $st = $pdo->prepare($sql);
    $st->execute($params);

    ok(['message' => 'Profilo aggiornato']);
  }

  if ($method === 'DELETE') {
    if ($id <= 0) err('id mancante', 422);
    if ($id !== $uid) err('Puoi eliminare solo il tuo account', 403);

    $st = $pdo->prepare("DELETE FROM Iscritto WHERE id_iscritto=?");
    $st->execute([$id]);

    session_destroy();

    ok(['message' => 'Account eliminato']);
  }

  err('Metodo non supportato', 405);

} catch (PDOException $e) {
  err('Errore DB: '.$e->getMessage(), 400);
} catch (Exception $e) {
  err('Errore server', 500);
}
<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data=null, int $code=200): void { http_response_code($code); echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err(string $m, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$m]); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$uid = (int)$_SESSION['user_id'];

try {
  if ($method === 'GET') {
    if ($id > 0) {
      $st = $pdo->prepare("SELECT id_iscritto, nome, cognome, data_nascita, ruolo, email, foto FROM Iscritto WHERE id_iscritto=?");
      $st->execute([$id]);
      $u = $st->fetch();
      if (!$u) err('Utente non trovato', 404);
      ok($u);
    } else {
      $st = $pdo->query("SELECT id_iscritto, nome, cognome, data_nascita, ruolo, email, foto FROM Iscritto");
      ok($st->fetchAll());
    }
  }

  if ($method === 'POST') {
    // Nota: questa è API admin-like; per creare utenti normalmente usi register.
    // Qui la lasciamo per aderire alla richiesta CRUD, ma proteggerla è un’assunzione progettuale.
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

    // campi aggiornabili (non email/password qui; puoi aggiungerli dopo)
    $nome = trim((string)($data['nome'] ?? ''));
    $cognome = trim((string)($data['cognome'] ?? ''));
    $ruolo = (string)($data['ruolo'] ?? '');
    $data_nascita = (string)($data['data_nascita'] ?? '');
    $foto = (string)($data['foto'] ?? '');

    // costruzione update dinamica semplice
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

    // Assunzione: cancellazione hard. In alternativa si può fare soft-delete con campo "attivo".
    $st = $pdo->prepare("DELETE FROM Iscritto WHERE id_iscritto=?");
    $st->execute([$id]);

    // logout
    session_destroy();

    ok(['message' => 'Account eliminato']);
  }

  err('Metodo non supportato', 405);

} catch (PDOException $e) {
  // Duplicate email ecc.
  err('Errore DB: '.$e->getMessage(), 400);
} catch (Exception $e) {
  err('Errore server', 500);
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/../common/config.php';
header('Content-Type: application/json; charset=utf-8');

function out(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  out(405, ['status' => 'error', 'message' => 'Metodo non supportato']);
}

$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
  out(422, ['status' => 'error', 'message' => 'Email e password obbligatorie']);
}

try {
  $stmt = $pdo->prepare("SELECT id_iscritto, nome, ruolo, email, password FROM Iscritto WHERE email = :email");
  $stmt->execute(['email' => $email]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password'])) {
    out(401, ['status' => 'error', 'message' => 'Credenziali errate']);
  }

  $_SESSION['user_id'] = (int)$user['id_iscritto'];
  $_SESSION['user_nome'] = (string)$user['nome'];
  $_SESSION['user_ruolo'] = (string)$user['ruolo'];

  out(200, ['status' => 'success', 'message' => 'Login effettuato con successo']);
} catch (Exception $e) {
  out(500, ['status' => 'error', 'message' => 'Errore server']);
}

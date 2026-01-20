<?php
declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
require_once __DIR__ . '/../common/api_auth.php';

header('Content-Type: application/json; charset=utf-8');

function ok($data = null) { echo json_encode(['ok'=>true,'data'=>$data]); exit; }
function err($msg, $code = 400) { http_response_code($code); echo json_encode(['ok'=>false,'message'=>$msg]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    err('Metodo non supportato', 405);
}

$ruolo = $_SESSION['user_ruolo'] ?? '';
if ($ruolo !== 'tecnico') {
    err('Solo i tecnici possono rimuovere la responsabilità', 403);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) err('ID utente mancante');

try {
    // Verifica se l'utente è responsabile di un settore
    $stmt = $pdo->prepare("SELECT id_settore FROM Settore WHERE id_responsabile = ?");
    $stmt->execute([$id]);
    $settore = $stmt->fetchColumn();

    if (!$settore) err('Utente non è responsabile di alcun settore', 404);

    // Rimuovi responsabilità
    $upd = $pdo->prepare("UPDATE Settore SET id_responsabile = 0 WHERE id_responsabile = ?");
    $upd->execute([$id]);

    ok(['message'=>'Responsabilità rimossa', 'id_settore'=>$settore]);
} catch (Exception $e) {
    err('Errore server: '.$e->getMessage(),500);
}

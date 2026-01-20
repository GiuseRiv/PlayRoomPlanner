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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) err('Parametro id mancante o non valido', 422);

try {
  $st = $pdo->prepare("
    SELECT
      p.id_prenotazione,
      p.data, p.ora_inizio, p.durata_ore, p.attivita, p.stato, p.data_creazione,
      p.id_sala, s.nome AS nome_sala, s.capienza,
      se.id_settore, se.nome AS nome_settore, se.tipo AS tipo_settore,
      p.id_organizzatore,
      CONCAT(org.nome,' ',org.cognome) AS organizzatore,
      org.ruolo AS ruolo_organizzatore
    FROM Prenotazione p
    JOIN Sala s ON s.id_sala = p.id_sala
    JOIN Settore se ON se.id_settore = s.id_settore
    JOIN Iscritto org ON org.id_iscritto = p.id_organizzatore
    WHERE p.id_prenotazione = ?
    LIMIT 1
  ");
  $st->execute([$id]);
  $p = $st->fetch();
  if (!$p) err('Prenotazione non trovata', 404);

  $st = $pdo->prepare("
    SELECT 1
    FROM invito i
    WHERE i.id_prenotazione = ? AND i.id_iscritto = ?
    LIMIT 1
  ");
  $st->execute([$id, $uid]);
  $isInvited = (bool)$st->fetchColumn();

  $isOrganizer = ((int)$p['id_organizzatore'] === $uid);
  $isTecnico = ((string)($_SESSION['user_ruolo'] ?? '') === 'tecnico');

  if (!$isOrganizer && !$isInvited && !$isTecnico) {
    err('Non autorizzato a vedere i dettagli di questa prenotazione', 403);
  }


  $st = $pdo->prepare("
    SELECT d.id_dotazione, d.nome
    FROM contiene c
    JOIN Dotazione d ON d.id_dotazione = c.id_dotazione
    WHERE c.id_sala = ?
    ORDER BY d.nome ASC
  ");
  $st->execute([(int)$p['id_sala']]);
  $dotazioni = $st->fetchAll();


  $st = $pdo->prepare("
    SELECT
      COUNT(*) AS tot,
      SUM(i.stato='accettato') AS accettati,
      SUM(i.stato='pendente') AS pendenti,
      SUM(i.stato='rifiutato') AS rifiutati
    FROM invito i
    JOIN Iscritto u ON u.id_iscritto = i.id_iscritto
    WHERE i.id_prenotazione = ?
      AND u.ruolo IN ('docente','allievo','tecnico') 
  ");
  $st->execute([$id]);
  $stats = $st->fetch() ?: ['tot'=>0,'accettati'=>0,'pendenti'=>0,'rifiutati'=>0];


  $st = $pdo->prepare("
    SELECT
      u.ruolo,
      COUNT(*) AS n,
      SUM(i.stato='accettato') AS accettati,
      SUM(i.stato='pendente') AS pendenti,
      SUM(i.stato='rifiutato') AS rifiutati
    FROM invito i
    JOIN Iscritto u ON u.id_iscritto = i.id_iscritto
    WHERE i.id_prenotazione = ?
      AND u.ruolo IN ('docente','allievo','tecnico')
    GROUP BY u.ruolo
    ORDER BY FIELD(u.ruolo, 'docente', 'tecnico', 'allievo')
  ");
  $st->execute([$id]);
  $byRole = $st->fetchAll();

 
  $st = $pdo->prepare("
    SELECT
      u.id_iscritto,
      u.nome, u.cognome, u.email,
      u.ruolo,
      i.stato,
      i.data_invio,
      i.data_risposta,
      i.motivazione_rifiuto
    FROM invito i
    JOIN Iscritto u ON u.id_iscritto = i.id_iscritto
    WHERE i.id_prenotazione = ?
      AND u.ruolo IN ('docente','allievo','tecnico')
    ORDER BY
      FIELD(i.stato,'pendente','accettato','rifiutato'),
      u.cognome ASC, u.nome ASC
  ");
  $st->execute([$id]);
  $invitati = $st->fetchAll();

  
  $viewerRole = (string)($_SESSION['user_ruolo'] ?? '');

  if ($viewerRole === 'allievo') {
      $invitati = []; 
  }
  
  if (!$isOrganizer && !empty($invitati)) {
     foreach ($invitati as &$inv) {
         unset($inv['data_risposta'], $inv['data_invio'], $inv['motivazione_rifiuto']);
     }
     unset($inv); 
  }


  ok([
    'prenotazione' => $p,
    'dotazioni' => $dotazioni,
    'inviti_stats' => [
      'tot' => (int)$stats['tot'],
      'accettati' => (int)$stats['accettati'],
      'pendenti' => (int)$stats['pendenti'],
      'rifiutati' => (int)$stats['rifiutati'],
      'by_role' => array_map(function($r){
        return [
          'ruolo' => $r['ruolo'],
          'tot' => (int)$r['n'],
          'accettati' => (int)$r['accettati'],
          'pendenti' => (int)$r['pendenti'],
          'rifiutati' => (int)$r['rifiutati'],
        ];
      }, $byRole),
    ],
    'invitati' => $invitati,
    'permissions' => [
      'is_organizer' => $isOrganizer,
      'is_invited' => $isInvited,
      'is_tecnico' => $isTecnico,
      'viewer_role' => $viewerRole
    ],
  ]);

} catch (Throwable $e) {
  err('Errore server: ' . $e->getMessage(), 500);
}
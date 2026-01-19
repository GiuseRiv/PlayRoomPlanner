<?php
declare(strict_types=1);

$nome = $_SESSION['user_nome'] ?? 'Utente';
$ruolo = $_SESSION['user_ruolo'] ?? '';

$uid = (int)($_SESSION['user_id'] ?? 0);

$isResponsabile = false;
if ($uid > 0) {
  $st = $pdo->prepare("SELECT 1 FROM Settore WHERE id_responsabile = ? LIMIT 1");
  $st->execute([$uid]);
  $isResponsabile = (bool)$st->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - Play Room Planner</title>

  <link rel="stylesheet" href="CSS/app.css">
  
</head>

<body class="bg-light">
  <div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <div class="text-muted small">Play Room Planner</div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <div class="text-muted">
          Benvenuto, <strong><?php echo htmlspecialchars($nome); ?></strong>
          <?php if ($ruolo !== ''): ?>
            <span class="ms-2 badge text-bg-secondary">ruolo: <?php echo htmlspecialchars($ruolo); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="index.php?page=profile">Profilo</a>
        <a class="btn btn-danger" href="index.php?page=logout">Logout</a>
      </div>
    </div>

    <!-- KPI -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="text-muted small">Inviti pendenti</div>
            <div class="d-flex align-items-baseline gap-2">
              <div class="display-6 mb-0" id="kpiPending">—</div>
              <span class="text-muted">da oggi</span>
            </div>
            <a href="index.php?page=invites" class="btn btn-sm btn-outline-primary mt-2">Apri inviti</a>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="text-muted small">Impegni futuri</div>
            <div class="d-flex align-items-baseline gap-2">
              <div class="display-6 mb-0" id="kpiAccepted">—</div>
              <span class="text-muted">accettati</span>
            </div>
            <a href="index.php?page=my_week" class="btn btn-sm btn-outline-primary mt-2">Vai a calendario</a>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="text-muted small">Prossimo impegno</div>
            <div class="fw-semibold" id="nextTitle">—</div>
            <div class="text-muted small" id="nextMeta">—</div>
            <a href="index.php?page=my_week" class="btn btn-sm btn-outline-primary mt-2">Dettagli</a>
          </div>
        </div>
      </div>
    </div>

    <div id="dashAlert"></div>

    <!-- Navigazione -->
    <div class="row g-3">

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <h2 class="h5 mb-2">Inviti</h2>
            <p class="text-muted mb-3">Accetta o rifiuta con motivazione e gestisci le prenotazioni future.</p>
            <a class="btn btn-primary" href="index.php?page=invites">Vai agli inviti</a>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <h2 class="h5 mb-2">I miei impegni (settimana)</h2>
            <p class="text-muted mb-3">Consulta gli impegni settimanali a partire da un giorno qualsiasi.</p>
            <a class="btn btn-primary" href="index.php?page=my_week">Apri calendario</a>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <h2 class="h5 mb-2">Sale prove</h2>
            <p class="text-muted mb-3">Visualizza sale e prenotazioni settimanali per sala.</p>
            <a class="btn btn-primary" href="index.php?page=rooms">Vai alle sale</a>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <h2 class="h5 mb-2">Gestione prenotazioni</h2>
            <p class="text-muted mb-3">Crea, modifica o cancella prenotazioni (solo responsabili).</p>

            <?php if ($isResponsabile): ?>
              <a class="btn btn-success" href="index.php?page=booking_new">Nuova prenotazione</a>
            <?php else: ?>
              <button class="btn btn-success" type="button" disabled>Nuova prenotazione</button>
              <div class="small text-muted mt-2">Funzione disponibile solo per responsabili.</div>
            <?php endif; ?>


          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h2 class="h5 mb-2">Operazioni & report</h2>
            <p class="text-muted mb-3">Conteggi e query richieste dal progetto (parte “Operazioni”).</p>
            <a class="btn btn-outline-primary" href="index.php?page=reports">Apri report</a>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="JS/dashboard.js"></script>
</body>
</html>

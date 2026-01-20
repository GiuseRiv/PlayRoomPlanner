<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - Play Room Planner</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="CSS/app.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

  <div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0">Dashboard</h1>
        <p class="text-muted mb-0">
          Benvenuto, <strong><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Utente'); ?></strong>
          <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($_SESSION['user_ruolo'] ?? ''); ?></span>
        </p>
      </div>
      <div class="d-flex gap-2">
        <a href="index.php?page=profile" class="btn btn-outline-secondary"><i class="bi bi-person-circle"></i> Profilo</a>
        <a href="index.php?page=logout" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    </div>

    <div class="row g-3 mb-4">
       <div class="col-md-4">
         <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
           <div class="card-body">
             <div class="text-muted small text-uppercase fw-bold">Inviti da gestire</div>
             <div class="d-flex align-items-center justify-content-between mt-2">
               <div class="h3 mb-0 fw-bold" id="kpiPending">-</div> 
               <i class="bi bi-envelope fs-1 text-primary opacity-25"></i>
             </div>
           </div>
         </div>
       </div>

       <div class="col-md-4">
         <div class="card shadow-sm border-0 border-start border-4 border-info h-100">
           <div class="card-body">
             <div class="text-muted small text-uppercase fw-bold">Questa settimana</div>
             <div class="d-flex align-items-center justify-content-between mt-2">
               <div class="h3 mb-0 fw-bold" id="kpiPlannedWeek">-</div>
               <i class="bi bi-calendar-week fs-1 text-info opacity-25"></i>
             </div>
           </div>
         </div>
       </div>

       <div class="col-md-4">
         <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
           <div class="card-body">
             <div class="text-muted small text-uppercase fw-bold">Prossimo impegno</div>
             <div class="mt-2 text-truncate fw-bold" id="nextTitle">Nessun impegno</div>
             <small class="text-muted d-block mb-2" id="nextWhen">--</small>
             <a href="#" id="nextDetailsBtn" class="btn btn-sm btn-outline-warning disabled">
                Dettagli <i class="bi bi-arrow-right-short"></i>
             </a>
           </div>
         </div>
       </div>
    </div>

    <h5 class="mb-3 text-secondary">Menu Rapido</h5>

    <div class="row g-4">
      
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-shadow transition">
          <div class="card-body">
            <h5 class="card-title text-primary"><i class="bi bi-envelope-open me-2"></i>Inviti</h5>
            <p class="card-text text-muted small">Gestisci gli inviti ricevuti.</p>
            <a href="index.php?page=invites" class="btn btn-primary w-100">Vai agli inviti</a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-shadow transition">
          <div class="card-body">
            <h5 class="card-title text-primary"><i class="bi bi-calendar-check me-2"></i>I miei impegni</h5>
            <p class="card-text text-muted small">Consulta il tuo calendario.</p>
            <a href="index.php?page=my_week" class="btn btn-primary w-100">Apri calendario</a>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-shadow transition">
          <div class="card-body">
            <h5 class="card-title text-primary"><i class="bi bi-music-note-beamed me-2"></i>Sale prove</h5>
            <p class="card-text text-muted small">Esplora sale e dotazioni.</p>
            <a href="index.php?page=rooms" class="btn btn-primary w-100">Vai alle sale</a>
          </div>
        </div>
      </div>
      
      <?php 
      $ruolo = $_SESSION['user_ruolo'] ?? '';
      ?>

      <?php if ($ruolo === 'tecnico' || $ruolo === 'docente'): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-shadow transition">
          <div class="card-body">
            <h5 class="card-title text-success"><i class="bi bi-plus-circle me-2"></i>Prenota</h5>
            <p class="card-text text-muted small">Crea una nuova prenotazione.</p>
            <a href="index.php?page=booking_new" class="btn btn-success w-100 text-white">Nuova prenotazione</a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($ruolo === 'tecnico' || $ruolo === 'docente'): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-shadow transition">
          <div class="card-body">
            <h5 class="card-title text-dark"><i class="bi bi-people me-2"></i>Elenco iscritti</h5>
            <p class="card-text text-muted small">
                <?php echo ($ruolo === 'tecnico') ? 'Amministra utenti e ruoli.' : 'Visualizza elenco iscritti.'; ?>
            </p>
            <a href="index.php?page=users_manage" class="btn btn-dark w-100">
                <?php echo ($ruolo === 'tecnico') ? 'Gestisci iscritti' : 'Consulta iscritti'; ?>
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($ruolo === 'tecnico'): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 h-100 hover-shadow transition">
          <div class="card-body">
            <h5 class="card-title text-primary"><i class="bi bi-bar-chart-line me-2"></i>Statistiche</h5>
            <p class="card-text text-muted small">Visualizza grafici utilizzo aule.</p>
            <a href="index.php?page=reports" class="btn btn-primary w-100">Apri report</a>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div> </div> <script src="js/dashboard.js"></script>
</body>
</html>
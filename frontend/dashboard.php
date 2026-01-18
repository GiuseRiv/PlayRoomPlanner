<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard - Play Room Planner</title>

  <link rel="stylesheet" href="CSS/app.css">
  <link rel="stylesheet" href="CSS/style.css">
</head>

<body class="bg-light">
  <div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <div class="text-muted">
          Benvenuto,
          <strong><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Utente'); ?></strong>
          (ruolo: <?php echo htmlspecialchars($_SESSION['user_ruolo'] ?? ''); ?>)
        </div>
      </div>

      <div class="d-flex gap-2 mt-2 mt-sm-0">
        <a class="btn btn-outline-secondary" href="index.php?page=profile">Profilo</a>
        <a class="btn btn-danger" href="index.php?page=logout">Logout</a>
      </div>
    </div>

    <div class="row g-3">

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h2 class="h5 mb-2">Inviti</h2>
            <p class="text-muted mb-3">
              Visualizza gli inviti ricevuti, accetta o rifiuta con motivazione, e controlla le prenotazioni future.
            </p>
            <a class="btn btn-primary" href="index.php?page=invites">Vai agli inviti</a>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h2 class="h5 mb-2">I miei impegni (settimana)</h2>
            <p class="text-muted mb-3">
              Consulta gli impegni settimanali a partire da un giorno qualsiasi della settimana.
            </p>
            <a class="btn btn-primary" href="index.php?page=my_week">Apri calendario</a>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h2 class="h5 mb-2">Sale prove</h2>
            <p class="text-muted mb-3">
              Visualizza le sale e le prenotazioni settimanali per sala.
            </p>
            <a class="btn btn-primary" href="index.php?page=rooms">Elenco sale</a>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h2 class="h5 mb-2">Gestione prenotazioni</h2>
            <p class="text-muted mb-3">
              Crea, modifica o cancella prenotazioni (solo per responsabili di settore).
            </p>

            <!-- Backend non modificato: lasciamo la tua condizione com'è -->
            <?php if (($_SESSION['user_ruolo'] ?? '') === 'responsabile'): ?>
              <a class="btn btn-success" href="index.php?page=booking_new">Nuova prenotazione</a>
            <?php else: ?>
              <button class="btn btn-success" type="button" disabled>Nuova prenotazione</button>
              <div class="small text-muted mt-2">
                Funzione disponibile solo per responsabili.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h2 class="h5 mb-2">Operazioni & report</h2>
            <p class="text-muted mb-3">
              Conteggi partecipanti/capienza, conteggio prenotazioni per giorno e sala, e query richieste dal progetto.
            </p>
            <a class="btn btn-outline-primary" href="index.php?page=reports">Apri report</a>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="JS/app.js"></script>
  <script src="JS/auth.js"></script>
</body>
</html>

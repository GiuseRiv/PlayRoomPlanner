<?php
declare(strict_types=1);

$day = $_GET['day'] ?? date('Y-m-d');

// calcolo lun-dom ISO: lunedì = 1, domenica = 7
$ts = strtotime($day);
if ($ts === false) {
  $day = date('Y-m-d');
  $ts = strtotime($day);
}
$dow = (int)date('N', $ts);

$mondayTs = strtotime("-" . ($dow - 1) . " day", $ts);
$sundayTs = strtotime("+" . (7 - $dow) . " day", $ts);

$monday = date('Y-m-d', $mondayTs);
$sunday = date('Y-m-d', $sundayTs);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>I miei impegni (settimana)</title>

  <link rel="stylesheet" href="CSS/app.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    .week-form .form-label { margin-bottom: .25rem; }
  </style>
</head>

<body class="bg-light">
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <a href="index.php?page=dashboard" class="btn btn-link p-0">&larr; Dashboard</a>
        <h1 class="h4 mt-2 mb-0">I miei impegni (settimana)</h1>
        <div class="text-muted small">
          Settimana selezionata:
          <strong id="weekRange"><?php echo htmlspecialchars($monday) . " → " . htmlspecialchars($sunday); ?></strong>
        </div>
      </div>
      <button id="btnRefresh" class="btn btn-outline-primary">Aggiorna</button>
    </div>

    <div class="card shadow-sm border-0 mt-3">
      <div class="card-body">

        <form id="weekForm" class="row g-2 align-items-end week-form">
          <div class="col-12 col-md-4">
            <label class="form-label">Scegli un giorno</label>
            <input type="date" class="form-control" name="day" id="dayInput"
                   value="<?php echo htmlspecialchars($day); ?>" required>
          </div>

          <div class="col-12 col-md-2 d-flex">
            <button type="submit" class="btn btn-primary w-100 align-self-end">Vai a settimana</button>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label">Stato</label>
            <select id="statusFilter" class="form-select">
              <option value="">Tutti</option>
              <option value="accettato">Accettati</option>
              <option value="pendente">Pendenti</option>
              <option value="rifiutato">Rifiutati</option>
            </select>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label">Cerca</label>
            <input id="textFilter" class="form-control" placeholder="Sala / attività / organizzatore">
          </div>
        </form>

        <hr>

        <div id="alertBox"></div>

        <div class="table-responsive mt-2">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Data</th>
                <th>Ora</th>
                <th>Durata</th>
                <th>Sala</th>
                <th>Attività</th>
                <th>Organizzatore</th>
                <th>Stato invito</th>
                <th class="text-end">Azioni</th>
              </tr>
            </thead>
            <tbody id="weekTbody">
              <tr><td colspan="8" class="text-muted">Caricamento...</td></tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>

  <!-- Bootstrap JS (bundle) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script src="JS/my_week.js"></script>
</body>
</html>

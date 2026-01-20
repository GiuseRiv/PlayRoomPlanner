<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reportistica - Play Room Planner</title>
  
  
  <link rel="stylesheet" href="CSS/app.css">
  
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

  <div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
       <a href="index.php?page=dashboard" class="btn btn-outline-secondary">&larr; Torna alla Dashboard</a>
       <h2 class="h4 mb-0">Reportistica e Statistiche</h2>
    </div>

    <div id="alertBox"></div>

    <div class="row g-4">
      
      <div class="col-md-7">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0">Popolarità Aule</h5>
          </div>
          <div class="card-body">
            <canvas id="roomsChart" style="max-height: 400px;"></canvas>
          </div>
        </div>
      </div>

      <div class="col-md-5">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0">Utenti più attivi</h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th class="ps-4">Utente</th>
                    <th class="text-end pe-4">Prenotazioni</th>
                  </tr>
                </thead>
                <tbody id="usersTbody">
                  <tr><td colspan="2" class="text-center py-3">Caricamento...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer bg-light text-muted small">
            Visualizzati i primi 5 utenti per numero di prenotazioni totali.
          </div>
        </div>
      </div>

    </div>
  </div>

  
  <script src="js/reports_view.js"></script>
</body>
</html>
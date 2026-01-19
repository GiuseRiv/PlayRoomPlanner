<?php
declare(strict_types=1);
require_once __DIR__ . '/../common/config.php'; // sessione + $pdo
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sale prove</title>
  <link rel="stylesheet" href="css/app.css">
</head>

<body class="bg-light">
  <div class="container py-4">
    <a href="index.php?page=dashboard" class="btn btn-link p-0">&larr; Dashboard</a>
    <h1 class="h4 mt-3">Sale prove</h1>

    <div class="card shadow-sm border-0 mt-3">
      <div class="card-body">

        <!-- Config: endpoints letti dal JS -->
        <div id="roomsPage"
             data-room-list-api="api/room_list.php"
             data-room-week-api="api/room_week.php"
             data-room-detail-api="api/room_detail.php"></div>

        <form id="roomsWeekForm" class="row g-2" autocomplete="off">
          <div class="col-12 col-md-5">
            <label class="form-label">Sala</label>
            <select id="roomSelect" class="form-select" required>
              <option value="">Caricamento sale…</option>
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Giorno della settimana</label>
            <input id="day" type="date" class="form-control" required>
          </div>

          <div class="col-12 col-md-3 align-self-end">
            <button class="btn btn-primary w-100" type="submit">Mostra prenotazioni</button>
          </div>
        </form>

        <hr>

        <div id="roomsAlert" class="alert alert-danger d-none" role="alert"></div>

        <!-- Riga info sala (niente blocco laterale) -->
<div class="d-flex flex-wrap gap-3 align-items-center mb-2">
  <div class="small">
    <strong>Sala:</strong> <span id="infoSalaNome" class="text-muted">-</span>
  </div>
  <div class="small">
    <strong>Settore:</strong> <span id="infoSalaSettore" class="text-muted">-</span>
  </div>
  <div class="small">
    <strong>Capienza:</strong> <span id="infoSalaCapienza" class="text-muted">-</span>
  </div>
  <div class="small">
    <strong>Dotazioni:</strong> <span id="infoSalaDotazioni" class="text-muted">-</span>
  </div>
</div>

<!-- Solo tabella prenotazioni -->
<div class="table-responsive">
  <table class="table table-sm align-middle mb-0">
    <thead>
      <tr>
        <th>Quando</th>
        <th>Attività</th>
        <th>Organizzatore</th>
        <th class="text-end">Durata</th>
      </tr>
    </thead>
    <tbody id="bookingsTbody">
      <tr><td colspan="4" class="text-muted">Seleziona una sala e una settimana.</td></tr>
    </tbody>
  </table>
</div>


      </div>
    </div>
  </div>

  <!-- JS esterno -->
  <script src="js/rooms_week.js"></script>
</body>
</html>

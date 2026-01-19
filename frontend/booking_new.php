<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nuova prenotazione</title>
  <link rel="stylesheet" href="CSS/app.css">
</head>

<body class="bg-light">
  <div class="container py-4">
    <a href="index.php?page=dashboard" class="btn btn-link p-0">&larr; Dashboard</a>
    <h1 class="h4 mt-3">Nuova prenotazione</h1>

    <div id="alertBox" class="mt-3"></div>

    <div class="card shadow-sm border-0 mt-3">
      <div class="card-body">
        <form id="bookingForm" class="row g-2">

          <div class="col-12">
            <label class="form-label">Sala</label>
            <select class="form-select" name="id_sala" id="roomSelect" required>
              <option value="">Caricamento sale...</option>
            </select>
            <div class="form-text">Scegli la sala da prenotare.</div>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Data</label>
            <input type="date" class="form-control" name="data" required>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Ora inizio (9-23)</label>
            <input type="number" class="form-control" name="ora_inizio" min="9" max="23" required>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label">Durata (ore)</label>
            <input type="number" class="form-control" name="durata_ore" min="1" max="14" required>
          </div>

          <div class="col-12">
            <label class="form-label">Attività</label>
            <input type="text" class="form-control" name="attivita" placeholder="Prove musicali, teatro, ballo...">
          </div>

          <hr class="mt-3">

          <div class="col-12 col-md-4">
            <label class="form-label">Invita</label>
            <select class="form-select" name="invite_mode" id="inviteMode">
              <option value="none">Nessuno (solo prenotazione)</option>
              <option value="all">Tutti gli iscritti</option>
              <option value="sector">Iscritti di un settore</option>
              <option value="role">Iscritti per ruolo</option>
            </select>
          </div>

          <div class="col-12 col-md-4" id="sectorBox" style="display:none;">
            <label class="form-label">Settore</label>
            <select class="form-select" id="sectorSelect">
              <option value="">Seleziona settore...</option>
            </select>
          </div>

          <div class="col-12 col-md-4" id="roleBox" style="display:none;">
            <label class="form-label">Ruolo</label>
            <select class="form-select" id="roleSelect">
              <option value="allievo">allievo</option>
              <option value="docente">docente</option>
              <option value="tecnico">tecnico</option>
            </select>
          </div>

          <div class="col-12 mt-2 d-flex gap-2">
            <button class="btn btn-success" type="submit">Crea prenotazione</button>
            <a class="btn btn-outline-secondary" href="index.php?page=dashboard">Annulla</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="JS/booking_new.js"></script>
</body>
</html>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nuova prenotazione</title>
  <link rel="stylesheet" href="CSS/app.css">
  <link rel="stylesheet" href="CSS/style.css">
</head>
<body class="bg-light">
  <div class="container py-4">
    <a href="index.php?page=dashboard" class="btn btn-link p-0">&larr; Dashboard</a>
    <h1 class="h4 mt-3">Nuova prenotazione</h1>

    <div class="card shadow-sm border-0 mt-3">
      <div class="card-body">
        <form class="row g-2">
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

          <div class="col-12 mt-2">
            <button class="btn btn-success" type="submit">Salva (TODO)</button>
          </div>
        </form>

        <hr>
        <p class="text-muted mb-0">
          TODO: scelta sala + controlli di disponibilità + inviti.
        </p>
      </div>
    </div>
  </div>
</body>
</html>

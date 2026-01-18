<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sale prove</title>
  <link rel="stylesheet" href="CSS/app.css">
  <link rel="stylesheet" href="CSS/style.css">
</head>
<body class="bg-light">
  <div class="container py-4">
    <a href="index.php?page=dashboard" class="btn btn-link p-0">&larr; Dashboard</a>
    <h1 class="h4 mt-3">Sale prove</h1>

    <div class="card shadow-sm border-0 mt-3">
      <div class="card-body">
        <form class="row g-2">
          <div class="col-12 col-md-4">
            <label class="form-label">Giorno della settimana</label>
            <input type="date" class="form-control" name="day">
          </div>
          <div class="col-12 col-md-3 align-self-end">
            <button class="btn btn-primary w-100" type="submit">Mostra prenotazioni</button>
          </div>
        </form>

        <hr>
        <p class="text-muted mb-0">
          TODO: elenco sale + prenotazioni settimanali per sala.
        </p>
      </div>
    </div>
  </div>
</body>
</html>

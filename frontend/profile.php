<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profilo</title>
  <link rel="stylesheet" href="CSS/app.css">
  <link rel="stylesheet" href="CSS/style.css">
</head>
<body class="bg-light">
  <div class="container py-4">
    <a href="index.php?page=dashboard" class="btn btn-link p-0">&larr; Dashboard</a>
    <h1 class="h4 mt-3">Profilo</h1>

    <div class="card shadow-sm border-0 mt-3">
      <div class="card-body">
        <p class="mb-1"><strong>Nome:</strong> <?php echo htmlspecialchars($_SESSION['user_nome'] ?? ''); ?></p>
        <p class="mb-1"><strong>Ruolo:</strong> <?php echo htmlspecialchars($_SESSION['user_ruolo'] ?? ''); ?></p>
        <p class="mb-0 text-muted">TODO: pagina modifica profilo + upload foto.</p>
      </div>
    </div>
  </div>
</body>
</html>

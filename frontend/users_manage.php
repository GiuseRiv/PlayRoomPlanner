<?php 
declare(strict_types=1); 
// Avvio sessione se non attiva
if (session_status() === PHP_SESSION_NONE) session_start();

// Controllo sicurezza base: se non sei loggato, via
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestione iscritti - Play Room Planner</title>
  <link rel="stylesheet" href="CSS/app.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

  <div class="container py-4">
    
    <input type="hidden" id="myUserRole" value="<?php echo htmlspecialchars($_SESSION['user_ruolo'] ?? ''); ?>">

    <div class="d-flex justify-content-between align-items-center mb-3">
       <div>
         <a href="index.php?page=dashboard" class="btn btn-outline-secondary">&larr; Torna alla Dashboard</a>
         <h1 class="h4 mt-2 mb-0">Elenco Iscritti</h1>
       </div>
    </div>

    <div id="alertBox"></div>

    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-3">
            <select id="roleFilter" class="form-select">
              <option value="">Tutti i ruoli</option>
              <option value="allievo">Allievi</option>
              <option value="docente">Docenti</option>
              <option value="tecnico">Tecnici</option>
            </select>
          </div>
          <div class="col-md-3">
            <input id="searchInput" class="form-control" placeholder="Cerca...">
          </div>
          <div class="col-md-3">
            <select id="sectorFilter" class="form-select">
              <option value="">Tutti i settori</option>
            </select>
          </div>
          <div class="col-md-3">
            <button id="btnRefresh" class="btn btn-primary w-100">Aggiorna</button>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th onclick="sortTable('id_iscritto')" style="cursor: pointer;">
                  ID <i class="bi bi-arrow-down-up small text-muted ms-1"></i>
                </th>
                <th>Foto</th>
                <th onclick="sortTable('cognome')" style="cursor: pointer;">
                 Nome e Cognome <i class="bi bi-arrow-down-up small text-muted ms-1"></i>
                </th>
                <th onclick="sortTable('ruolo')" style="cursor: pointer;">
                 Ruolo <i class="bi bi-arrow-down-up small text-muted ms-1"></i>
                </th>
                <th onclick="sortTable('email')" style="cursor: pointer;">
                 Email <i class="bi bi-arrow-down-up small text-muted ms-1"></i>
                </th>
                <th>Settori</th>
                <th class="text-end">Azioni</th>
              </tr>
            </thead>
            <tbody id="usersTbody">
              <tr><td colspan="7" class="text-center p-3">Caricamento in corso...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  
  <script src="js/users_manage.js?v=<?php echo time(); ?>"></script>
</body>
</html>
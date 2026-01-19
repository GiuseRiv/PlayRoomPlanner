<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Play Room Planner - Registrazione</title>
  <link rel="stylesheet" href="/PlayRoomPlanner/CSS/app.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6">

        <div class="card shadow-sm border-0">
          <div class="card-body p-4">
            <div class="text-center mb-4">
              <h1 class="h4 mb-1">Crea account</h1>
              <div class="text-muted small">Play Room Planner</div>
            </div>

            <div id="regFeedback"></div>

            <form id="registrationForm" enctype="multipart/form-data" novalidate>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Nome</label>
                  <input type="text" name="nome" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Cognome</label>
                  <input type="text" name="cognome" class="form-control" required>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Password</label>
                  <input type="password" name="password" id="regPassword" class="form-control" required minlength="8">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Conferma Password</label>
                  <input type="password" name="password_confirm" id="regPasswordConfirm" class="form-control" required>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Data nascita</label>
                <input type="date" name="data_nascita" id="regDataNascita" class="form-control" required>
              </div>

              <div class="mb-4">
                <label class="form-label">Foto profilo</label>
                <input type="file" name="foto" class="form-control" accept="image/*">
              </div>

              <button type="submit" class="btn btn-primary w-100" id="btnRegister">
                Registrati
              </button>

              <div class="text-center mt-3">
                <a href="/PlayRoomPlanner/index.php?page=login" class="text-decoration-none">
                  Hai già un account? Accedi
                </a>
              </div>
            </form>

          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="/PlayRoomPlanner/JS/registration.js"></script>
</body>
</html>
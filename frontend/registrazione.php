<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Play Room Planner - Registrazione</title>
  <link rel="stylesheet" href="/PlayRoomPlanner/CSS/app.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    .password-wrapper { position: relative; }
    .toggle-password {
      position: absolute; right: 12px; top: 38px;
      cursor: pointer; color: #6c757d; font-size: 1.2rem; z-index: 10;
    }
    .toggle-password:hover { color: #0d6efd; }
  </style>
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

            <div id="globalFeedback" class="mb-3"></div>

            <form id="registrationForm" enctype="multipart/form-data" novalidate>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Nome</label>
                  <input type="text" name="nome" id="fieldNome" class="form-control" required>
                  <div class="invalid-feedback">Inserisci un nome valido (min. 2 caratteri).</div>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Cognome</label>
                  <input type="text" name="cognome" id="fieldCognome" class="form-control" required>
                  <div class="invalid-feedback">Inserisci un cognome valido (min. 2 caratteri).</div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="fieldEmail" class="form-control" required>
                <div class="invalid-feedback">Inserisci un indirizzo email valido.</div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3 password-wrapper">
                  <label class="form-label">Password</label>
                  <input type="password" name="password" id="regPassword" class="form-control" required>
                  <i class="bi bi-eye toggle-password" onclick="togglePass('regPassword', this)"></i>
                  <div class="invalid-feedback" id="passErrorMsg">Password non sicura.</div>
                </div>
                
                <div class="col-md-6 mb-3 password-wrapper">
                  <label class="form-label">Conferma Password</label>
                  <input type="password" name="password_confirm" id="regPasswordConfirm" class="form-control" required>
                  <i class="bi bi-eye toggle-password" onclick="togglePass('regPasswordConfirm', this)"></i>
                  <div class="invalid-feedback">Le password non coincidono.</div>
                </div>
              </div>

              <div id="passwordRules" class="password-requirements mb-3 d-none">
                  <div class="small fw-bold mb-2">La password deve contenere:</div>
                  <div class="req-item" id="req-length"><i class="bi bi-circle"></i> Minimo 8 caratteri</div>
                  <div class="req-item" id="req-upper"><i class="bi bi-circle"></i> Una lettera maiuscola</div>
                  <div class="req-item" id="req-special"><i class="bi bi-circle"></i> Un carattere speciale (!@#$...)</div>
                  <div class="req-item" id="req-match"><i class="bi bi-circle"></i> Le password coincidono</div>
              </div>

              <div class="mb-3">
                <label class="form-label">Data nascita</label>
                <input type="date" name="data_nascita" id="regDataNascita" class="form-control" required>
                <div class="invalid-feedback">Data non valida (non può essere futura).</div>
              </div>

              <div class="mb-4">
                <label class="form-label">Foto profilo <small class="text-muted">(facoltativo)</small></label>
                <input type="file" name="foto" class="form-control" accept="image/*">
              </div>

              <button type="submit" class="btn btn-primary w-100" id="btnRegister">Registrati</button>

              <div class="text-center mt-3">
                <a href="/PlayRoomPlanner/index.php?page=login" class="text-decoration-none">Hai già un account? Accedi</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function togglePass(id, icon) {
      const input = document.getElementById(id);
      if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("bi-eye", "bi-eye-slash");
      } else {
        input.type = "password";
        icon.classList.replace("bi-eye-slash", "bi-eye");
      }
    }
    
    // Limite data JS
    document.addEventListener('DOMContentLoaded', () => {
        const dateInput = document.getElementById('regDataNascita');
        if(dateInput) dateInput.max = new Date().toISOString().split('T')[0];
    });
  </script>
  <script src="js/registration.js"></script>
</body>
</html>
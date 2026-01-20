<?php 
// Avvio sessione per recuperare l'ID utente loggato
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Controllo base di sicurezza: se non c'è user_id, rimanda al login
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
  <title>Il mio Profilo - Play Room Planner</title>
  
  <link rel="stylesheet" href="CSS/app.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

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
    
    <div class="d-flex justify-content-between align-items-center mb-4">
       <a href="index.php?page=dashboard" class="btn btn-outline-secondary">
         <i class="bi bi-arrow-left"></i> Torna alla Dashboard
       </a>
       <h2 class="h4 mb-0 fw-bold text-primary">Gestione Profilo</h2>
    </div>

    <div id="alertBox"></div>
    
    <input type="hidden" id="currentUserId" value="<?php echo $_SESSION['user_id']; ?>">

    <div class="row g-4">
      
      <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body text-center d-flex flex-column justify-content-center">
            
            <div class="mb-3 position-relative d-inline-block mx-auto">
               <img id="profilePreview" src="images/default.png" alt="Foto Profilo" 
                    class="rounded-circle border border-3 border-white shadow-sm" 
                    style="width: 150px; height: 150px; object-fit: cover;"> 
               
               <button class="btn btn-primary position-absolute bottom-0 end-0 rounded-circle shadow border-white border-2" 
                       style="width:40px; height:40px; padding:0;"
                       onclick="document.getElementById('uploadFoto').click();"
                       title="Cambia foto">
                  <i class="bi bi-camera-fill"></i>
               </button>
            </div>
            
            <input type="file" id="uploadFoto" accept="image/png, image/jpeg, image/gif" class="d-none">
            
            <h5 id="displayNome" class="fw-bold mb-1">Caricamento...</h5>
            <div>
                <span id="displayRuolo" class="badge bg-secondary mb-3">...</span>
            </div>
            <p class="small text-muted">Clicca sulla fotocamera per aggiornare la tua immagine.</p>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white py-3 border-bottom-0">
            <h5 class="mb-0 fw-bold text-primary">
                <i class="bi bi-person-lines-fill me-2"></i>Dati Personali
            </h5>
          </div>
          <div class="card-body pt-0">
            <form id="profileForm">
              
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Matricola</label>
                    <input type="text" id="fieldMatricola" class="form-control fw-bold bg-light" disabled readonly>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-bold text-muted text-uppercase">Ruolo</label>
                    <input type="text" id="fieldRuolo" class="form-control bg-light" disabled readonly>
                </div>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-bold">Nome</label>
                  <input type="text" id="fieldNome" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold">Cognome</label>
                  <input type="text" id="fieldCognome" class="form-control" required>
                </div>
              </div>

              <div class="row g-3 mt-1">
                  <div class="col-md-8">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" id="fieldEmail" class="form-control bg-light" readonly>
                    <div class="form-text small">L'email non può essere modificata.</div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-bold">Data di Nascita</label>
                    <input type="date" id="fieldDataNascita" class="form-control bg-light" readonly disabled>
                  </div>
              </div>

              <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Salva Anagrafica
                </button>
              </div>
            </form>
          </div>
        </div>

        <div id="respSection" class="card shadow-sm border-0 mb-4 d-none">
            <div class="card-header bg-warning bg-opacity-10 py-3 border-bottom-0">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-star-fill text-warning me-2"></i>Dati Responsabile Settore
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-light border d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-info-circle-fill text-primary me-2"></i>
                    <div>
                        Attualmente sei responsabile del settore: <strong id="respSettoreNome" class="text-uppercase text-dark"></strong>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Anni di Servizio</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-calendar-check"></i></span>
                            <input type="text" id="respAnni" class="form-control bg-light fw-bold" readonly disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted text-uppercase">Data di Nomina</label>
                        <input type="date" id="respData" class="form-control bg-light fw-bold" readonly disabled>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h5 class="mb-0 fw-bold text-danger">
                    <i class="bi bi-shield-lock-fill me-2"></i>Sicurezza Account
                </h5>
            </div>
            <div class="card-body pt-0">
                <form id="passwordForm">
                    
                    <div class="mb-3 password-wrapper">
                        <label class="form-label">Password Attuale</label>
                        <input type="password" id="oldPass" class="form-control" placeholder="Inserisci la password attuale per conferma" required>
                        <i class="bi bi-eye toggle-password" onclick="togglePass('oldPass', this)"></i>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6 password-wrapper">
                            <label class="form-label">Nuova Password</label>
                            <input type="password" id="newPass" class="form-control" placeholder="Min 8 caratteri" required>
                            <i class="bi bi-eye toggle-password" onclick="togglePass('newPass', this)"></i>
                        </div>
                        
                        <div class="col-md-6 password-wrapper">
                            <label class="form-label">Conferma Nuova</label>
                            <input type="password" id="confirmPass" class="form-control" placeholder="Ripeti la password" required>
                            <i class="bi bi-eye toggle-password" onclick="togglePass('confirmPass', this)"></i>
                        </div>
                    </div>

                    <div id="passwordRules" class="password-requirements mb-3 mt-3 d-none p-3 bg-light rounded border">
                        <div class="small fw-bold mb-2 text-dark">La nuova password deve contenere:</div>
                        <div class="req-item text-muted" id="req-length"><i class="bi bi-circle me-2"></i> Minimo 8 caratteri</div>
                        <div class="req-item text-muted" id="req-upper"><i class="bi bi-circle me-2"></i> Una lettera maiuscola</div>
                        <div class="req-item text-muted" id="req-special"><i class="bi bi-circle me-2"></i> Un carattere speciale (!@#$...)</div>
                        <div class="req-item text-muted" id="req-match"><i class="bi bi-circle me-2"></i> Le password coincidono</div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-outline-danger" id="btnSavePass">
                            Aggiorna Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

      </div> </div> </div> <script>
    // Funzione per mostrare/nascondere la password (Copiata da registrazione)
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
  </script>

  <script src="js/profile.js"></script>
</body>
</html>
<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Il mio Profilo - Play Room Planner</title>
  <link rel="stylesheet" href="CSS/app.css">
</head>
<body class="bg-light">

  <div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
       <a href="index.php?page=dashboard" class="btn btn-outline-secondary">&larr; Torna alla Dashboard</a>
       <h2 class="h4 mb-0">Gestione Profilo</h2>
    </div>

    <div id="alertBox"></div>
    <input type="hidden" id="currentUserId" value="<?php echo $_SESSION['user_id']; ?>">

    <div class="row g-4">
      <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body text-center">
            <div class="mb-3 position-relative d-inline-block">
               <img id="profilePreview" src="" alt="Foto" 
                    class="rounded-circle profile-img-lg" 
                    style="object-fit: cover;"> 
               <button class="btn btn-dark position-absolute bottom-0 end-0 rounded-circle shadow border-white border-2" 
                       style="width:40px; height:40px; padding:0;"
                       onclick="document.getElementById('uploadFoto').click();">
                  <i class="bi bi-camera-fill"></i>
               </button>
            </div>
            <input type="file" id="uploadFoto" accept="image/*" class="d-none">
            
            <h5 id="displayNome" class="fw-bold mb-1">...</h5>
            <span id="displayRuolo" class="badge bg-secondary mb-3">...</span>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white py-3 border-bottom-0">
            <h5 class="mb-0 fw-bold text-primary">Dati Personali</h5>
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

              <div class="mt-3">
                <label class="form-label fw-bold">Email</label>
                <input type="email" id="fieldEmail" class="form-control bg-light" readonly>
              </div>

              <div class="mt-3">
                <label class="form-label fw-bold">Data di Nascita</label>
                <input type="date" id="fieldDataNascita" class="form-control bg-light" readonly disabled>
              </div>

              <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary px-4">Salva Anagrafica</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h5 class="mb-0 fw-bold text-danger">Sicurezza Account</h5>
            </div>
            <div class="card-body pt-0">
                <form id="passwordForm">
                    <div class="mb-3">
                        <label class="form-label">Password Attuale</label>
                        <input type="password" id="oldPass" class="form-control" placeholder="Inserisci la password attuale per conferma" required>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nuova Password</label>
                            <input type="password" id="newPass" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Conferma Nuova</label>
                            <input type="password" id="confirmPass" class="form-control" required>
                        </div>
                    </div>

                    <div id="passwordRules" class="password-requirements mb-3 mt-3 d-none">
                        <div class="small fw-bold mb-2">La nuova password deve contenere:</div>
                        <div class="req-item" id="req-length"><i class="bi bi-circle"></i> Minimo 8 caratteri</div>
                        <div class="req-item" id="req-upper"><i class="bi bi-circle"></i> Una lettera maiuscola</div>
                        <div class="req-item" id="req-special"><i class="bi bi-circle"></i> Un carattere speciale (!@#$...)</div>
                        <div class="req-item" id="req-match"><i class="bi bi-circle"></i> Le password coincidono</div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-outline-danger" id="btnSavePass">Aggiorna Password</button>
                    </div>
                </form>
            </div>
        </div>

      </div>
    </div>
  </div>

  <script src="js/profile.js"></script>
</body>
</html>
<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifica Profilo - Play Room Planner</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <link rel="stylesheet" href="CSS/app.css">
</head>
<body class="bg-light">

  <div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
       <a href="index.php?page=dashboard" class="btn btn-outline-secondary">&larr; Torna alla lista</a>
       <h2 class="h4 mb-0">Gestione Profilo Utente</h2>
    </div>

    <div id="alertBox"></div>

    <div class="row">
      
      <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body text-center">
            
            <div class="mb-3">
               <img id="displayFoto" src="" alt="Foto Utente" class="rounded-circle profile-img-lg">
            </div>
            
            <h5 id="displayNomeCompleto" class="fw-bold mb-1">Caricamento...</h5>
            <p id="displayRuoloBadge" class="mb-3"></p>

            <hr>

            <div class="text-start">
              <div class="mb-2">
                <small class="text-muted d-block">Email</small>
                <span id="displayEmail" class="fw-bold text-break">-</span>
              </div>
              <div class="mb-2">
                <small class="text-muted d-block">Data di Nascita</small>
                <span id="displayDataNascita" class="fw-bold">-</span>
              </div>
              <div class="mb-2">
                <small class="text-muted d-block">ID Iscritto</small>
                <span id="displayId" class="font-monospace">-</span>
              </div>
            </div>

          </div>
          <div class="card-footer bg-light text-muted small text-center">
            Dati anagrafici non modificabili
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0">Modifica Permessi e Ruolo</h5>
          </div>
          <div class="card-body p-4">
            
            <form id="editForm">
              <input type="hidden" id="userId">

              <div class="mb-4">
                <label for="fieldRuolo" class="form-label fw-bold">Ruolo nel sistema</label>
                <select id="fieldRuolo" class="form-select form-select-lg" required>
                  <option value="allievo">Allievo</option>
                  <option value="docente">Docente</option>
                  <option value="tecnico">Tecnico (Admin)</option>
                </select>
                <div class="form-text">Modificare il ruolo cambia i permessi di accesso.</div>
              </div>

              <div class="mb-4" id="boxSettori">
                <label for="fieldSettori" class="form-label fw-bold">Settori di competenza</label>
                <div class="d-flex justify-content-between align-items-center mb-1">
                   <small class="text-muted">Tieni premuto CTRL per selezione multipla.</small>
                   <small class="text-primary cursor-pointer" onclick="document.getElementById('fieldSettori').selectedIndex = -1;">Deseleziona tutto</small>
                </div>
                
                <select id="fieldSettori" class="form-select" multiple size="6">
                  </select>
              </div>

              <div id="msgTecnico" class="alert alert-info d-none">
                <i class="bi bi-info-circle"></i> <strong>Nota:</strong> Il ruolo "Tecnico" ha accesso trasversale a tutti i settori.
              </div>

              <hr class="mt-4">

              <div class="d-flex justify-content-end gap-2">
                <a href="index.php?page=users_manage" class="btn btn-outline-secondary">Annulla</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold">Salva Modifiche</button>
              </div>

            </form>

          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/users_edit.js"></script>
</body>
</html>
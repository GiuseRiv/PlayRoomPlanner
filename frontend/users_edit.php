<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifica Profilo</title>
  <link rel="stylesheet" href="CSS/app.css">
  <style>
      .disabled-area { opacity: 0.6; pointer-events: none; background-color: #f8f9fa; }
      .resp-box { border-left: 4px solid #ffc107; background-color: #fffbf0; padding: 15px; margin-top: 10px; border-radius: 4px; }
  </style>
</head>
<body class="bg-light">

  <div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <a href="index.php?page=users_manage" class="btn btn-outline-secondary">&larr; Torna alla lista</a>
      <h2 class="h4 mb-0">Gestione Profilo Utente</h2>
    </div>

    <div id="alertBox"></div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body text-center">
            <div class="mb-3">
              <img id="displayFoto" src="images/default.png" alt="Foto" class="rounded-circle" style="width:120px; height:120px; object-fit:cover; background:#eee;">
            </div>
            
            <h5 id="displayNomeCompleto" class="fw-bold mb-1">...</h5>
            
            <p id="displayRuoloBadge" class="mb-2 badge bg-secondary"></p>
            
            <div id="displayResponsabilita" class="d-none"></div>

            <hr>

            <div class="text-start">
              <p class="mb-2"><small class="text-muted d-block">Email</small><span id="displayEmail" class="fw-bold">-</span></p>
              <p class="mb-2"><small class="text-muted d-block">Nascita</small><span id="displayDataNascita">-</span></p>
              <p class="mb-2"><small class="text-muted d-block">ID</small><span id="displayId" class="font-monospace">-</span></p>
              
              <div class="mt-3 pt-3 border-top">
                <small class="text-muted d-block fw-bold text-uppercase mb-2">Competenze Attuali</small>
                <div id="displaySettori" class="small mb-2 fw-medium">-</div>
                <div class="row mt-2">
                    <div class="col-6">
                        <small class="text-muted d-block">Anni Attività</small>
                        <span id="displayAnniAttivita" class="fw-bold">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Data Nomina</small>
                        <span id="displayDataIncarico" class="fw-bold">-</span>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0">Modifica Permessi</h5>
          </div>
          <div class="card-body p-4">
            
            <form id="editForm">
              
              <div class="mb-4">
                <label for="fieldRuolo" class="form-label fw-bold">Ruolo</label>
                <select id="fieldRuolo" class="form-select form-select-lg">
                  <option value="allievo">Allievo</option>
                  <option value="docente">Docente</option>
                  <option value="tecnico">Tecnico</option>
                </select>
                <div id="msgTecnico" class="alert alert-info mt-2 d-none small">
                   Il Tecnico ha accesso a tutti i settori.
                </div>
              </div>

              <div class="mb-4" id="boxSettori">
                <label for="fieldSettori" class="form-label fw-bold">Settori di competenza</label>
                <div class="d-flex justify-content-between mb-1">
                    <small class="text-muted">CTRL+Click selezione multipla</small>
                    <small class="text-primary cursor-pointer" onclick="document.getElementById('fieldSettori').selectedIndex = -1;">Resetta</small>
                </div>
                <select id="fieldSettori" class="form-select" multiple size="5"></select>
              </div>

              <div id="containerResponsabile" style="display:none;" class="mb-4">
                  <div class="form-check form-switch p-3 bg-white border rounded d-flex align-items-center gap-3">
                      <input class="form-check-input m-0" type="checkbox" id="checkResponsabile" style="width: 3em; height: 1.5em; cursor:pointer;">
                      <label class="form-check-label fw-bold mb-0" for="checkResponsabile" style="cursor:pointer;">Nomina Responsabile di Settore</label>
                  </div>

                  <div id="dettagliResponsabile" class="resp-box" style="display:none;">
                      <div class="mb-3">
                          <label class="form-label small text-muted text-uppercase fw-bold">Settore da assegnare</label>
                          <select id="selectSettoreResp" class="form-select"></select>
                          <div id="msgResponsabileOccupato" class="alert alert-warning mt-2 small d-none"></div>
                          <div class="form-text mt-2"><small>La data di nomina verrà registrata automaticamente (oggi).</small></div>
                      </div>
                  </div>
              </div>

              <hr class="mt-4">
              <div class="text-end">
                <button type="submit" class="btn btn-primary px-4 fw-bold">Salva Modifiche</button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="js/users_edit.js"></script>
</body>
</html>
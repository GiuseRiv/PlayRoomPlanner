<?php
declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nuova prenotazione</title>
  <link rel="stylesheet" href="CSS/app.css">
</head>

<body class="bg-light">
  <div class="container py-4">
    <a href="index.php?page=dashboard" class="btn btn-outline-secondary mb-3">&larr; Torna alla Dashboard</a>
    
    <h1 class="h4 fw-bold mb-4">Nuova prenotazione</h1>

    <div id="alertBox"></div>

    <form id="bookingForm" autocomplete="off">
      
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">Dettagli Evento</div>
        <div class="card-body">
          <div class="row g-3">
            
            <div class="col-12">
              <label class="form-label fw-bold">Sala</label>
              <select class="form-select" name="id_sala" id="roomSelect" required>
                <option value="">Caricamento sale...</option>
              </select>
              <div class="form-text">Mostra solo le sale disponibili per il tuo livello di accesso.</div>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label fw-bold">Data</label>
              <input type="date" class="form-control" name="data" id="dateInput" required>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label fw-bold">Ora inizio (9-22)</label>
              <select class="form-select" name="ora_inizio" id="startSelect" required>
                <option value="">Seleziona...</option>
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label fw-bold">Durata</label>
              <select class="form-select" name="durata_ore" id="durSelect" required>
                <option value="">Seleziona...</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold">Attività / Titolo</label>
              <input type="text" class="form-control" name="attivita" placeholder="Es. Prove musicali, riunione tecnica..." required>
            </div>

          </div>
        </div>
      </div>

      <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-header bg-light fw-bold text-primary">
            <i class="bi bi-envelope-plus"></i> Inviti
        </div>
        <div class="card-body">
          
          <div class="row g-3 align-items-end">
            
            <div class="col-12 col-md-4">
                <label class="form-label fw-bold">Chi vuoi invitare?</label>
                <select class="form-select" id="inviteMode">
                    <option value="none" selected>Nessuno (solo prenotazione)</option>
                    <option value="all">Tutti gli iscritti</option>
                    <option value="sector">Iscritti di un settore</option>
                    <option value="role">Iscritti per ruolo</option>
                    <option value="custom">Personalizza (Avanzato)</option>
                </select>
            </div>

            <div class="col-12 col-md-4" id="boxSimpleRole" style="display:none;">
                <label class="form-label fw-bold">Seleziona Ruolo</label>
                <select class="form-select" id="simpleRoleSelect">
                    <option value="allievo">Allievo</option>
                    <option value="docente">Docente</option>
                    <option value="tecnico">Tecnico</option>
                </select>
            </div>

            <div class="col-12 col-md-4" id="boxSimpleSector" style="display:none;">
                <label class="form-label fw-bold">Seleziona Settore</label>
                <select class="form-select" id="simpleSectorSelect">
                    <option value="">Scegli settore...</option>
                </select>
            </div>

            <div class="col-12" id="boxCustom" style="display:none;">
                <div class="card card-body bg-light border mt-2">
                    <h6 class="fw-bold mb-3">Selezione Avanzata</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="fw-bold small text-muted mb-2 text-uppercase">Ruoli</div>
                            <div class="form-check">
                                <input class="form-check-input custom-role" type="checkbox" value="allievo" id="cxRoleAllievo">
                                <label class="form-check-label" for="cxRoleAllievo">Allievi</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input custom-role" type="checkbox" value="docente" id="cxRoleDocente">
                                <label class="form-check-label" for="cxRoleDocente">Docenti</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input custom-role" type="checkbox" value="tecnico" id="cxRoleTecnico">
                                <label class="form-check-label" for="cxRoleTecnico">Tecnici</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="fw-bold small text-muted mb-2 text-uppercase">Settori</div>
                            <div id="customSectorsContainer">
                                <span class="text-muted small">Caricamento...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="form-text text-muted">
                    Gli inviti verranno inviati immediatamente dopo la conferma della prenotazione.
                </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 pb-5">
        <button class="btn btn-success btn-lg px-4 fw-bold" type="submit">Crea Prenotazione</button>
        <a class="btn btn-outline-secondary btn-lg" href="index.php?page=dashboard">Annulla</a>
      </div>

    </form>
  </div>

  <script src="js/booking_new.js"></script>
</body>
</html>
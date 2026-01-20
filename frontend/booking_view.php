<?php declare(strict_types=1); 
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dettagli Impegno</title>
  <link rel="stylesheet" href="CSS/app.css">
</head>
<body class="bg-light">

<div class="container py-4">

  <script>
    window.BOOKING_ID = <?php echo (int)($_GET['id'] ?? 0); ?>;
  </script>
  <input type="hidden" id="currentUserId" value="<?php echo $_SESSION['user_id']; ?>">
  <input type="hidden" id="currentUserRole" value="<?php echo $_SESSION['user_ruolo'] ?? ''; ?>">

  <div class="mb-3 d-flex justify-content-between align-items-center">
    <a href="index.php?page=dashboard" class="btn btn-outline-secondary">&larr; Torna alla Dashboard</a>
    
    <a href="index.php?page=booking_edit&id=<?php echo (int)($_GET['id'] ?? 0); ?>" 
       id="btnEditBooking" 
       class="btn btn-primary d-none">
        <i class="bi bi-pencil-square me-1"></i> Modifica
    </a>
  </div>

  <div id="alertBox"></div>

  <div class="mb-3">
    <div class="text-muted small text-uppercase fw-bold">Dettaglio Prenotazione</div>
    <h1 class="h3 mb-0" id="headerTitle">Caricamento...</h1>
  </div>

  <div id="loadingSpinner" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Caricamento...</span>
      </div>
  </div>

  <div id="mainContent" class="d-none">
      
      <div class="row g-4 mb-4">
          <div class="col-md-6">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 pb-0 pt-3">
                  <div class="fw-bold text-primary text-uppercase small">Info Generali</div>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-6">
                      <div class="text-muted small">Giorno</div>
                      <div class="fw-bold" id="dataDisplay">-</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted small">Ora e Durata</div>
                      <div class="fw-bold" id="oraDurataDisplay">-</div>
                    </div>
                    <div class="col-12">
                      <div class="text-muted small">Attività</div>
                      <div class="fw-bold fs-5" id="attivita">-</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted small">Stato Attuale</div>
                      <div id="statoBadge">-</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted small">Data creazione</div>
                      <div id="creato">-</div>
                    </div>
                    <div class="col-12 border-top pt-2 mt-2">
                      <div class="text-muted small">Organizzatore</div>
                      <div class="fw-bold" id="organizzatore">-</div>
                    </div>
                  </div>
                </div>
              </div>
          </div>

          <div class="col-md-6">
              <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 pb-0 pt-3">
                  <div class="fw-bold text-primary text-uppercase small">Location & Risorse</div>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-12">
                      <div class="text-muted small">Aula</div>
                      <div class="fw-bold fs-4 text-dark" id="sala">-</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted small">Settore</div>
                      <div class="fw-bold">
                        <span id="settore">-</span> 
                        <br><small class="text-muted fw-normal" id="tipoSettore"></small>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted small">Capienza Max</div>
                      <div class="fw-bold fs-5"><span id="capienza">-</span> <span class="fs-6 text-muted fw-normal">posti</span></div>
                    </div>
                    <div class="col-12">
                      <div class="text-muted small mb-2">Dotazioni incluse</div>
                      <div id="dotazioni"></div>
                    </div>
                  </div>
                </div>
              </div>
          </div>
      </div>

      <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-white border-0 pt-3 pb-0">
          <div class="d-flex justify-content-between align-items-center">
              <div class="fw-bold text-primary text-uppercase small">Partecipazione & Occupazione</div>
          </div>
        </div>
        <div class="card-body">
          <div class="mb-4 p-4 bg-light rounded-3 border">
              <div class="d-flex justify-content-between align-items-end mb-2">
                  <div>
                    <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Occupazione Posti</span>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="fs-3 fw-bold text-dark lh-1" id="occupancyText">0 / 0</span>
                        <span class="text-muted small">presenti</span>
                    </div>
                  </div>
                  <span id="fullCapacityBadge" class="badge bg-danger shadow-sm d-none px-3 py-2">
                      <i class="bi bi-exclamation-triangle-fill me-1"></i> SOLD OUT
                  </span>
              </div>
              <div class="progress shadow-inset bg-white border" style="height: 24px; border-radius: 12px;">
                  <div id="occupancyBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; border-radius: 12px; transition: width 0.8s ease-in-out;"></div>
              </div>
              <div class="text-muted small mt-2 d-flex align-items-center">
                <i class="bi bi-info-circle me-1"></i>
                <span class="fst-italic" style="font-size: 0.85rem;">Il conteggio include l'organizzatore (1) e gli invitati che hanno confermato.</span>
              </div>
          </div>

          <div class="alert alert-light border mb-4 p-3 d-flex align-items-center shadow-sm">
              <div class="me-3 bg-white p-2 rounded-circle border text-primary"><i class="bi bi-pie-chart-fill fs-4"></i></div>
              <div>
                  <div class="small text-uppercase text-muted fw-bold">Panoramica Inviti</div>
                  <div id="roleBreakdown" class="text-dark fw-semibold">Caricamento statistiche...</div>
              </div>
          </div>

          <div id="tableContainer" class="d-none">
              <div class="d-flex align-items-center mb-2 mt-4">
                  <h6 class="small text-muted text-uppercase fw-bold mb-0 ps-1">Lista Partecipanti</h6>
                  <div class="ms-auto"><span class="badge bg-light text-dark border fw-normal">Dettaglio completo</span></div>
              </div>
              <div class="table-responsive border rounded bg-white">
                 <table class="table table-hover align-middle mb-0">
                   <thead class="table-light small">
                     <tr><th class="ps-3 py-3">Nominativo</th><th>Contatto Email</th><th>Ruolo</th><th>Stato Invito</th></tr>
                   </thead>
                   <tbody id="invTbody" class="bg-white"></tbody>
                 </table>
              </div>
          </div>

          <div id="hiddenListMessage" class="d-none mt-3 text-center py-5 border border-dashed rounded bg-light-subtle">
              <div class="mb-3 text-secondary opacity-50"><i class="bi bi-shield-lock-fill" style="font-size: 3rem;"></i></div>
              <h6 class="fw-bold text-dark">Elenco Partecipanti Protetto</h6>
              <p class="small text-muted px-4 mb-0 mx-auto" style="max-width: 500px;">
                  In conformità con le policy di privacy, l'elenco dettagliato dei nominativi è accessibile esclusivamente all'organizzatore, ai docenti e allo staff tecnico.
              </p>
          </div>
        </div>
      </div>
  </div> 
</div>

<script src="js/booking_view.js"></script> 
</body>
</html>
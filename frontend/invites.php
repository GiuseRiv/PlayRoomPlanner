<?php
declare(strict_types=1);
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inviti - Play Room Planner</title>

  <link rel="stylesheet" href="CSS/app.css">
</head>

<body class="bg-light">
  <div class="container py-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <a href="index.php?page=dashboard" class="btn btn-outline-secondary">&larr; Torna alla Dashboard</a>
        <h1 class="h4 mt-2 mb-0">Inviti</h1>
        <div class="text-muted small">
          Qui trovi le prenotazioni a cui sei stato invitato, puoi accettare o rifiutare con motivazione.
        </div>
      </div>

      <div class="d-flex gap-2">
        <button id="btnRefresh" class="btn btn-outline-primary">Aggiorna</button>
      </div>
    </div>

    <div id="alertBox" class="mt-3"></div>

    <div class="card shadow-sm border-0 mt-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Data</th>
                <th>Ora</th>
                <th>Durata</th>
                <th>Sala</th>
                <th>Attività</th>
                <th>Stato</th>
                <th class="text-end">Azioni</th>
              </tr>
            </thead>
            <tbody id="invitesTbody">
              <tr>
                <td colspan="7" class="text-muted">Caricamento...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal motivazione rifiuto -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form id="rejectForm" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Rifiuta invito</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="rejectPrenotazioneId" name="id_prenotazione" />
            <div class="mb-2">
              <label for="rejectReason" class="form-label">Motivazione (obbligatoria)</label>
              <textarea id="rejectReason" class="form-control" rows="4" maxlength="500" required></textarea>
              <div class="form-text">Max 500 caratteri.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
            <button type="submit" class="btn btn-danger">Conferma rifiuto</button>
          </div>
        </form>
      </div>
    </div>

  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="JS/invites.js"></script>
</body>
</html>

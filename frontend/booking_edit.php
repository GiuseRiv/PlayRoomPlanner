<?php declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

require_once __DIR__ . '/../common/config.php';

$id_prenotazione = (int)($_GET['id'] ?? 0);
$readonly = false;
$warning_msg = "";

// Controllo se l'evento è già iniziato o passato
if($id_prenotazione > 0) {
    $stmt = $pdo->prepare("SELECT data, ora_inizio FROM Prenotazione WHERE id_prenotazione = ?");
    $stmt->execute([$id_prenotazione]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($p) {
        $inizio_evento = strtotime($p['data'] . ' ' . sprintf('%02d', $p['ora_inizio']) . ':00:00');
        if(time() >= $inizio_evento) {
            $readonly = true;
            $warning_msg = "Questo evento è già iniziato o passato. Non è possibile modificarlo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifica Prenotazione</title>
    <link rel="stylesheet" href="CSS/app.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            
            <div class="d-flex align-items-center mb-4">
                <a href="javascript:history.back()" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i> Annulla
                </a>
                <h2 class="h4 mb-0 fw-bold">Modifica Prenotazione</h2>
            </div>

            <div id="alertBox">
                <?php if($readonly): ?>
                    <div class="alert alert-warning shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $warning_msg; ?>
                    </div>
                <?php endif; ?>
            </div>

            <form id="editForm">
                <fieldset <?php echo $readonly ? 'disabled' : ''; ?>>
                    <input type="hidden" id="bookingId" value="<?php echo $id_prenotazione; ?>">

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white fw-bold">Dettagli Evento</div>
                        <div class="card-body p-4">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Titolo Attività</label>
                                <input type="text" id="fieldAttivita" class="form-control" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Data</label>
                                    <input type="date" id="fieldData" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Sala</label>
                                    <select id="fieldSala" class="form-select" required>
                                        <option value="" disabled selected>Caricamento...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold">Ora Inizio</label>
                                    <select id="fieldOra" class="form-select" required>
                                        <?php for($i=9; $i<=22; $i++) echo "<option value='$i'>$i:00</option>"; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold">Durata</label>
                                    <select id="fieldDurata" class="form-select" required>
                                        <option value="1">1 ora</option>
                                        <option value="2">2 ore</option>
                                        <option value="3">3 ore</option>
                                        <option value="4">4 ore</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body p-4">
                            
                            <div id="inviteInfoBox" class="alert alert-info border-info small mb-3">
                                <i class="bi bi-info-circle-fill me-1"></i> 
                                La lista degli invitati <strong>non verrà modificata</strong>. Gli inviti precedenti restano validi.
                            </div>

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" id="modifyInvitesToggle" style="cursor: pointer;">
                                <label class="form-check-label fw-bold" for="modifyInvitesToggle">
                                    Modifica la lista degli invitati
                                </label>
                            </div>

                            <div id="inviteControlsContainer" style="display:none;">
                                <div class="row g-3 align-items-end">
                                    
                                    <div class="col-12 col-md-4">
                                        <label class="form-label fw-bold">Nuova selezione</label>
                                        <select class="form-select" id="inviteMode">
                                            <option value="none" disabled selected>Scegli chi invitare...</option>
                                            <option value="all">Tutti gli iscritti</option>
                                            <option value="sector">Iscritti di un settore</option>
                                            <option value="role">Iscritti per ruolo</option>
                                            <option value="custom">Personalizza (Avanzato)</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-4" id="boxSimpleRole" style="display:none;">
                                        <label class="form-label fw-bold">Ruolo</label>
                                        <select class="form-select" id="simpleRoleSelect">
                                            <option value="allievo">Allievo</option>
                                            <option value="docente">Docente</option>
                                            <option value="tecnico">Tecnico</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-4" id="boxSimpleSector" style="display:none;">
                                        <label class="form-label fw-bold">Settore</label>
                                        <select class="form-select" id="simpleSectorSelect">
                                            <option value="">Caricamento...</option>
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
                                    
                                </div> </div> </div>
                    </div>

                    <div class="d-grid gap-2 mb-5">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold">
                            Salva Modifiche e Invia
                        </button>
                    </div>

                </fieldset>
            </form>
        </div>
    </div>
</div>

<script src="js/booking_edit.js"></script>
</body>
</html>
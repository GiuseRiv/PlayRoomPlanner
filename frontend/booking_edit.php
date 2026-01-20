<?php declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
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
        <div class="col-md-8 col-lg-6">
            
            <div class="d-flex align-items-center mb-4">
                <a href="javascript:history.back()" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i> Annulla
                </a>
                <h2 class="h4 mb-0 fw-bold">Modifica Prenotazione</h2>
            </div>

            <div id="alertBox"></div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    
                    <form id="editForm">
                        <input type="hidden" id="bookingId" value="<?php echo (int)($_GET['id'] ?? 0); ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold">Titolo Attività</label>
                            <input type="text" id="fieldAttivita" class="form-control form-control-lg" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Data</label>
                                <input type="date" id="fieldData" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Sala</label>
                                <select id="fieldSala" class="form-select" required>
                                    <option value="" disabled selected>Caricamento sale...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label fw-bold">Ora Inizio</label>
                                <select id="fieldOra" class="form-select" required>
                                    <?php for($i=9; $i<=22; $i++) echo "<option value='$i'>$i:00</option>"; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Durata (ore)</label>
                                <select id="fieldDurata" class="form-select" required>
                                    <option value="1">1 ora</option>
                                    <option value="2">2 ore</option>
                                    <option value="3">3 ore</option>
                                    <option value="4">4 ore</option>
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-info small d-flex align-items-center">
                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                            <div>
                                Modificando data, ora o sala, il sistema verificherà nuovamente la disponibilità.
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">
                                Salva Modifiche
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="js/booking_edit.js"></script>
</body>
</html>
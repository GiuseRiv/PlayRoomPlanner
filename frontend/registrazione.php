<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Play Room Planner - Registrazione</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4 shadow border-0" style="border-radius: 1rem;">
                <h3 class="text-center mb-4">Crea Account</h3>
                <div id="regFeedback"></div>
                
                <!-- Aggiunto enctype per gestire i file -->
                <form id="registrationForm" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cognome</label>
                            <input type="text" name="cognome" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <!-- NUOVO: Campo Password -->
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ruolo</label>
                            <select name="ruolo" class="form-select">
                                <option value="allievo">Allievo</option>
                                <option value="docente">Docente</option>
                                <option value="tecnico">Tecnico</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data Nascita</label>
                            <input type="date" name="data_nascita" class="form-control" required>
                        </div>
                    </div>

                    <!-- NUOVO: Campo Foto -->
                    <div class="mb-4">
                        <label class="form-label">Foto Profilo</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2">Registrati</button>
                    <div class="text-center mt-3">
                        <a href="login_view.php" class="text-decoration-none">Hai già un account? Accedi</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Assicurati che il percorso del JS sia corretto -->
<script src="/PlayRoomPlanner/js/registration.js"></script>
</body>
</html>
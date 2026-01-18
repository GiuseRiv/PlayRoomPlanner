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

                <form id="registrationForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" id="regNome" name="nome" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cognome</label>
                            <input type="text" id="regCognome" name="cognome" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" id="regEmail" name="email" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ruolo</label>
                            <select id="regRuolo" name="ruolo" class="form-select">
                                <option value="allievo">Allievo</option>
                                <option value="docente">Docente</option>
                                <option value="tecnico">Tecnico</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data Nascita</label>
                            <input type="date" id="regData" name="data_nascita" class="form-control" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2">Registrati</button>

                    <div class="text-center mt-3">
                        <a href="index.php?page=login" class="text-decoration-none">Hai già un account? Accedi</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<script src="JS/registration.js"></script>
</body>
</html>

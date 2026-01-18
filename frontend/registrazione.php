<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione - Play Room Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4 text-center">Registrati</h4>
                    <div id="regFeedback"></div>
                    <form id="registerForm" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col"><label>Nome</label><input type="text" name="nome" class="form-control" required></div>
                            <div class="col"><label>Cognome</label><input type="text" name="cognome" class="form-control" required></div>
                        </div>
                        <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="mb-3"><label>Data Nascita</label><input type="date" name="data_nascita" class="form-control" required></div>
                        <div class="mb-3">
                            <label>Foto Profilo</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Crea Account</button>
                    </form>
                    <div class="mt-3 text-center"><a href="../login_view.php">Torna al Login</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../js/auth.js"></script>
</body>
</html>
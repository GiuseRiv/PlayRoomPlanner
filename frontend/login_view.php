<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Play Room Planner - Login</title>
    <link rel="stylesheet" href="CSS/app.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .login-container { margin-top: 10%; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 0.5rem 1rem 0 rgba(0, 0, 0, 0.1); }
        .brand-logo { color: #0d6efd; font-weight: 700; font-size: 1.5rem; text-align: center; display: block; text-decoration: none; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
<div class="container login-container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <a href="#" class="brand-logo">Play Room Planner</a>
            <div id="loginFeedback"></div>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'reg_ok'): ?>
                <div class="alert alert-success">Registrazione riuscita! Accedi ora.</div>
            <?php endif; ?>
            <div class="card p-4">
                <div class="card-body">
                    <h5 class="card-title text-center mb-4">Accedi</h5>
                    <form id="loginForm">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Entra</button>
                    </form>
                    <hr>
                    <div class="text-center">
                        <p class="small">Nuovo iscritto? <a href="frontend/registrazione.php">Registrati ora</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="js/auth.js"></script>
</body>
</html>
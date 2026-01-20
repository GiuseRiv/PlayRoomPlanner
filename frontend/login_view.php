<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Play Room Planner - Login</title>
    <link rel="stylesheet" href="CSS/app.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .login-container { margin-top: 10%; }
        .password-wrapper { position: relative; }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 38px;
            cursor: pointer;
            color: #6c757d;
            font-size: 1.2rem;
        }
        .toggle-password:hover { color: #0d6efd; }
    </style>
</head>
<body class="bg-light">
<div class="container login-container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <h2 class="text-center mb-4">Play Room Planner</h2>
            <div id="loginFeedback"></div>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'reg_ok'): ?>
                <div class="alert alert-success">Registrazione riuscita! Accedi ora.</div>
            <?php endif; ?>
            <div class="card p-4 shadow-sm border-0">
                <div class="card-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3 password-wrapper">
                            <label class="form-label">Password</label>
                            <input type="password" id="loginPass" name="password" class="form-control" required>
                            <i class="bi bi-eye toggle-password" onclick="toggleLoginPass(this)"></i>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Entra</button>
                    </form>
                    <div class="text-center mt-3">
                        <p class="small">Nuovo? <a href="/PlayRoomPlanner/index.php?page=registrazione">Registrati</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function toggleLoginPass(icon) {
        const x = document.getElementById("loginPass");
        if (x.type === "password") {
            x.type = "text";
            icon.classList.replace("bi-eye", "bi-eye-slash");
        } else {
            x.type = "text" === "password" ? "text" : "password";
            x.type = "password";
            icon.classList.replace("bi-eye-slash", "bi-eye");
        }
    }
</script>
<script src="js/auth.js"></script>
</body>
</html>
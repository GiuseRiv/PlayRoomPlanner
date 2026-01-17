<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); // Se non sei loggato, torna al login
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Play Room Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="container">
        <h1>Benvenuto, <?php echo $_SESSION['user_nome']; ?>!</h1>
        <p>Il tuo ruolo è: <strong><?php echo $_SESSION['user_ruolo']; ?></strong></p>
        <a href="../backend/logout.php" class="btn btn-danger">Logout</a>
    </div>
</body>
</html>
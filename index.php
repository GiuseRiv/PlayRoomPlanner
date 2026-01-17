<?php
session_start();
// Se l'utente è già loggato, lo manda alla dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: frontend/dashboard.php");
    exit();
}
// Altrimenti carica la vista del login
include('frontend/login_view.php');
?>
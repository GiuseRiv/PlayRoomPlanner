<?php
// common/auth_check.php
require_once('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login_view.php");
    exit();
}
?>
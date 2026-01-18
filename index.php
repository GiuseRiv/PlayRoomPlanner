<?php
declare(strict_types=1);
session_start();

$page = $_GET['page'] ?? 'dashboard';

// pagine pubbliche
$publicPages = ['login', 'registrazione'];

// guard session
if (!isset($_SESSION['user_id']) && !in_array($page, $publicPages, true)) {
    header('Location: index.php?page=login');
    exit;
}

$routes = [
    'login'         => __DIR__ . '/frontend/login_view.php',
    'registrazione' => __DIR__ . '/frontend/registrazione.php',
    'dashboard'     => __DIR__ . '/frontend/dashboard.php',
    'logout'        => __DIR__ . '/backend/logout.php',

    // pagine collegate dalla dashboard
    'profile'       => __DIR__ . '/frontend/profile.php',
    'invites'       => __DIR__ . '/frontend/invites.php',
    'my_week'       => __DIR__ . '/frontend/my_week.php',
    'rooms'         => __DIR__ . '/frontend/rooms.php',
    'booking_new'   => __DIR__ . '/frontend/booking_new.php',
    'reports'       => __DIR__ . '/frontend/reports.php',
];

if (!isset($routes[$page])) {
    http_response_code(404);
    echo "404 - Pagina non trovata";
    exit;
}

require $routes[$page];
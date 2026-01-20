<?php
declare(strict_types=1);
require_once __DIR__ . '/common/config.php'; 
$page = $_GET['page'] ?? 'dashboard';


$publicPages = ['login', 'registrazione'];


if (!isset($_SESSION['user_id']) && !in_array($page, $publicPages, true)) {
    header('Location: index.php?page=login');
    exit;
}

$routes = [
    'login'         => __DIR__ . '/frontend/login_view.php',
    'registrazione' => __DIR__ . '/frontend/registrazione.php',
    'dashboard'     => __DIR__ . '/frontend/dashboard.php',
    'logout'        => __DIR__ . '/backend/logout.php',

    
    'profile'       => __DIR__ . '/frontend/profile.php',
    'invites'       => __DIR__ . '/frontend/invites.php',
    'my_week'       => __DIR__ . '/frontend/my_week.php',
    'rooms'         => __DIR__ . '/frontend/rooms.php',
    'booking_new'   => __DIR__ . '/frontend/booking_new.php',
    'reports'       => __DIR__ . '/frontend/reports_view.php',
    'users_manage'  => __DIR__ . '/frontend/users_manage.php',
    'users_edit'    => __DIR__ . '/frontend/users_edit.php',
    'booking_view'  => __DIR__ . '/frontend/booking_view.php',
    'booking_edit'  => __DIR__ . '/frontend/booking_edit.php',
    
];

if (!isset($routes[$page])) {
    http_response_code(404);
    echo "404 - Pagina non trovata";
    exit;
}

require $routes[$page];

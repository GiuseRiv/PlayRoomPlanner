<?php
declare(strict_types=1);
require_once __DIR__ . '/../common/config.php';

$_SESSION = [];
session_destroy();

header('Location: /PlayRoomPlanner/index.php?page=login');
exit;

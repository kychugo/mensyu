<?php
/**
 * index.php — Main router
 */

require_once __DIR__ . '/includes/session.php';

$page = $_GET['page'] ?? 'home';
$allowed = [
    'home', 'learning', 'games', 'teahouse',
    'translate', 'profile', 'login', 'register',
];

// API requests handled separately
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    // let .htaccess route directly
    http_response_code(404);
    exit('Not found');
}

if (!in_array($page, $allowed, true)) {
    $page = 'home';
}

$file = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($file)) {
    http_response_code(404);
    include __DIR__ . '/pages/home.php';
    exit;
}

include $file;

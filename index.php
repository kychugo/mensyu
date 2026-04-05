<?php
/**
 * index.php — Main router
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/db.php';       // also triggers auto-install on first run
require_once __DIR__ . '/includes/error_tracker.php';
require_once __DIR__ . '/includes/geo_guard.php';

// ── Geo guard (HK-only + VPN block) ──────────────────────────────
geo_guard();

// ── Pseudo-cron: auto-post every N hours (no cmd needed) ─────────
// Only checked on ~1-in-10 requests to keep the overhead minimal.
if (rand(1, 10) === 1) {
    index_pseudo_cron();
}

$page = $_GET['page'] ?? 'home';
$allowed = [
    'home', 'learning', 'games', 'teahouse',
    'translate', 'profile', 'login', 'register',
    'admin',
];

// API requests handled separately
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    http_response_code(404);
    exit('Not found');
}

if (!in_array($page, $allowed, true)) {
    $page = 'home';
}

// Admin requires authentication
if ($page === 'admin' && !session_is_admin()) {
    header('Location: /login');
    exit;
}

// ── Usage tracking (page view) ────────────────────────────────────
$user_id_for_tracking = session_check_auth() ? (session_get_user()['id'] ?? null) : null;
usage_track($page, 'view', $user_id_for_tracking);

$file = __DIR__ . '/pages/' . $page . '.php';
if (!file_exists($file)) {
    http_response_code(404);
    include __DIR__ . '/pages/home.php';
    exit;
}

include $file;

// ── Pseudo-cron helper ────────────────────────────────────────────
function index_pseudo_cron(): void {
    try {
        $last     = (int)db_query(
            "SELECT setting_value FROM app_settings WHERE setting_key='cron_last_run'"
        )->fetchColumn();
        $interval = (int)(db_query(
            "SELECT setting_value FROM app_settings WHERE setting_key='cron_interval_hours'"
        )->fetchColumn() ?: 2);

        if (time() - $last < $interval * 3600) {
            return; // not time yet
        }

        // Mark ran immediately to prevent duplicate runs from concurrent requests
        db_query(
            "UPDATE app_settings SET setting_value=? WHERE setting_key='cron_last_run'",
            [(string)time()]
        );

        // Run the cron function (defined in cron_post.php)
        define('_CRON_INTERNAL', true);
        require_once __DIR__ . '/api/cron_post.php';
        cron_run();
    } catch (Throwable $e) {
        // Silent fail — cron failure must never break page delivery
    }
}

<?php
/**
 * api/admin.php — Admin REST API
 *
 * All endpoints require admin session.
 * GET  ?action=dashboard|users|errors|usage|posts|settings
 * POST action=toggle_admin|toggle_ban|delete_user|clear_errors|
 *             delete_post|delete_comment|run_cron|update_setting
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth guard ────────────────────────────────────────────────────
if (!session_is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '需要管理員權限']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $method === 'GET' ? ($_GET['action'] ?? '') : ($_POST['action'] ?? '');

// ── GET endpoints ─────────────────────────────────────────────────
if ($method === 'GET') {
    match ($action) {
        'dashboard' => admin_dashboard(),
        'users'     => admin_users(),
        'errors'    => admin_errors(),
        'usage'     => admin_usage(),
        'posts'     => admin_posts(),
        'settings'  => admin_settings(),
        default     => json_out(['success' => false, 'message' => 'Unknown action']),
    };
    exit;
}

// ── POST endpoints ────────────────────────────────────────────────
if ($method === 'POST') {
    // CSRF check
    $csrf = $_POST['csrf_token'] ?? '';
    if (!csrf_token_verify($csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    match ($action) {
        'toggle_admin'    => admin_toggle_admin(),
        'toggle_ban'      => admin_toggle_ban(),
        'delete_user'     => admin_delete_user(),
        'clear_errors'    => admin_clear_errors(),
        'delete_post'     => admin_delete_post(),
        'delete_comment'  => admin_delete_comment(),
        'run_cron'        => admin_run_cron(),
        'update_setting'  => admin_update_setting(),
        default           => json_out(['success' => false, 'message' => 'Unknown action']),
    };
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);

// ── GET handlers ──────────────────────────────────────────────────

function admin_dashboard(): void {
    $now    = date('Y-m-d');
    $week   = date('Y-m-d', strtotime('-7 days'));

    $stats = [
        'total_users'     => (int)db_query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'total_posts'     => (int)db_query('SELECT COUNT(*) FROM teahouse_posts')->fetchColumn(),
        'total_errors'    => (int)db_query('SELECT COUNT(*) FROM error_logs')->fetchColumn(),
        'errors_today'    => (int)db_query('SELECT COUNT(*) FROM error_logs WHERE DATE(created_at)=?', [$now])->fetchColumn(),
        'views_today'     => (int)db_query('SELECT COUNT(*) FROM usage_stats WHERE DATE(created_at)=?', [$now])->fetchColumn(),
        'views_week'      => (int)db_query('SELECT COUNT(*) FROM usage_stats WHERE created_at>=?', [$week])->fetchColumn(),
        'users_today'     => (int)db_query('SELECT COUNT(*) FROM users WHERE DATE(created_at)=?', [$now])->fetchColumn(),
        'cron_last_run'   => (int)(db_query("SELECT setting_value FROM app_settings WHERE setting_key='cron_last_run'")->fetchColumn() ?: 0),
    ];

    $recent_errors = db_query(
        'SELECT id, error_level, message, url, created_at FROM error_logs ORDER BY created_at DESC LIMIT 5'
    )->fetchAll();

    $recent_users = db_query(
        'SELECT id, username, is_admin, created_at FROM users ORDER BY created_at DESC LIMIT 5'
    )->fetchAll();

    json_out(['success' => true, 'stats' => $stats, 'recent_errors' => $recent_errors, 'recent_users' => $recent_users]);
}

function admin_users(): void {
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $off   = ($page - 1) * $limit;

    $users = db_query(
        'SELECT id, username, is_admin, is_banned, created_at FROM users ORDER BY id DESC LIMIT ? OFFSET ?',
        [$limit, $off]
    )->fetchAll();
    $total = (int)db_query('SELECT COUNT(*) FROM users')->fetchColumn();

    json_out(['success' => true, 'data' => $users, 'total' => $total, 'page' => $page]);
}

function admin_errors(): void {
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $level = $_GET['level'] ?? '';
    $limit = 30;
    $off   = ($page - 1) * $limit;

    if ($level !== '') {
        $rows  = db_query(
            'SELECT id, error_level, message, url, user_id, ip_address, created_at
             FROM error_logs WHERE error_level=? ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$level, $limit, $off]
        )->fetchAll();
        $total = (int)db_query('SELECT COUNT(*) FROM error_logs WHERE error_level=?', [$level])->fetchColumn();
    } else {
        $rows  = db_query(
            'SELECT id, error_level, message, url, user_id, ip_address, created_at
             FROM error_logs ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $off]
        )->fetchAll();
        $total = (int)db_query('SELECT COUNT(*) FROM error_logs')->fetchColumn();
    }

    json_out(['success' => true, 'data' => $rows, 'total' => $total, 'page' => $page]);
}

function admin_usage(): void {
    $days = max(1, min(90, (int)($_GET['days'] ?? 7)));

    // Page view counts grouped by page
    $by_page = db_query(
        'SELECT page, COUNT(*) as cnt FROM usage_stats
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND action="view"
         GROUP BY page ORDER BY cnt DESC',
        [$days]
    )->fetchAll();

    // Daily totals
    $daily = db_query(
        'SELECT DATE(created_at) as dt, COUNT(*) as cnt FROM usage_stats
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND action="view"
         GROUP BY DATE(created_at) ORDER BY dt ASC',
        [$days]
    )->fetchAll();

    // AI call count
    $ai_calls = (int)db_query(
        'SELECT COUNT(*) FROM usage_stats WHERE action LIKE "ai_%" AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)',
        [$days]
    )->fetchColumn();

    json_out(['success' => true, 'by_page' => $by_page, 'daily' => $daily, 'ai_calls' => $ai_calls, 'days' => $days]);
}

function admin_posts(): void {
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = 20;
    $off   = ($page - 1) * $limit;

    $posts = db_query(
        'SELECT id, username, content, post_type, created_at FROM teahouse_posts ORDER BY created_at DESC LIMIT ? OFFSET ?',
        [$limit, $off]
    )->fetchAll();
    $total = (int)db_query('SELECT COUNT(*) FROM teahouse_posts')->fetchColumn();

    foreach ($posts as &$p) {
        $p['content']  = db_escape($p['content']);
        $p['username'] = db_escape($p['username']);
    }

    json_out(['success' => true, 'data' => $posts, 'total' => $total, 'page' => $page]);
}

function admin_settings(): void {
    $rows = db_query('SELECT setting_key, setting_value FROM app_settings')->fetchAll();
    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
    json_out(['success' => true, 'data' => $settings]);
}

// ── POST handlers ─────────────────────────────────────────────────

function admin_toggle_admin(): void {
    $target_id = (int)($_POST['user_id'] ?? 0);
    $me        = session_get_user()['id'];

    if ($target_id === $me) {
        json_out(['success' => false, 'message' => '不能修改自己的管理員狀態']);
        return;
    }

    // Prevent removing the last admin
    $current = (int)db_query('SELECT is_admin FROM users WHERE id=?', [$target_id])->fetchColumn();
    if ($current === 1) {
        $admin_count = (int)db_query('SELECT COUNT(*) FROM users WHERE is_admin=1')->fetchColumn();
        if ($admin_count <= 1) {
            json_out(['success' => false, 'message' => '必須保留至少一個管理員']);
            return;
        }
    }

    db_query('UPDATE users SET is_admin = 1 - is_admin WHERE id=?', [$target_id]);
    json_out(['success' => true]);
}

function admin_toggle_ban(): void {
    $target_id = (int)($_POST['user_id'] ?? 0);
    $me        = session_get_user()['id'];
    if ($target_id === $me) {
        json_out(['success' => false, 'message' => '不能封禁自己']);
        return;
    }
    db_query('UPDATE users SET is_banned = 1 - is_banned WHERE id=?', [$target_id]);
    json_out(['success' => true]);
}

function admin_delete_user(): void {
    $target_id = (int)($_POST['user_id'] ?? 0);
    $me        = session_get_user()['id'];
    if ($target_id === $me) {
        json_out(['success' => false, 'message' => '不能刪除自己']);
        return;
    }
    db_query('DELETE FROM user_progress  WHERE user_id=?', [$target_id]);
    db_query('DELETE FROM achievements   WHERE user_id=?', [$target_id]);
    db_query('DELETE FROM teahouse_comments WHERE user_id=?', [$target_id]);
    db_query('DELETE FROM users          WHERE id=?', [$target_id]);
    json_out(['success' => true]);
}

function admin_clear_errors(): void {
    $level = $_POST['level'] ?? '';
    if ($level !== '') {
        db_query('DELETE FROM error_logs WHERE error_level=?', [$level]);
    } else {
        db_query('DELETE FROM error_logs');
    }
    json_out(['success' => true]);
}

function admin_delete_post(): void {
    $id = (int)($_POST['post_id'] ?? 0);
    db_query('DELETE FROM teahouse_comments WHERE post_id=?', [$id]);
    db_query('DELETE FROM teahouse_posts    WHERE id=?', [$id]);
    json_out(['success' => true]);
}

function admin_delete_comment(): void {
    $id = (int)($_POST['comment_id'] ?? 0);
    db_query('DELETE FROM teahouse_comments WHERE id=?', [$id]);
    json_out(['success' => true]);
}

function admin_run_cron(): void {
    try {
        db_query(
            "UPDATE app_settings SET setting_value=? WHERE setting_key='cron_last_run'",
            [(string)time()]
        );
        define('_CRON_INTERNAL', true);
        require_once __DIR__ . '/cron_post.php';
        cron_run();
        json_out(['success' => true, 'message' => '古人貼文已觸發']);
    } catch (Throwable $e) {
        json_out(['success' => false, 'message' => $e->getMessage()]);
    }
}

function admin_update_setting(): void {
    $key   = trim($_POST['setting_key']   ?? '');
    $value = trim($_POST['setting_value'] ?? '');

    $allowed = ['geo_guard_enabled', 'cron_interval_hours', 'platform_name'];
    if (!in_array($key, $allowed, true)) {
        json_out(['success' => false, 'message' => 'Unknown setting']);
        return;
    }

    db_query(
        'UPDATE app_settings SET setting_value=? WHERE setting_key=?',
        [$value, $key]
    );
    json_out(['success' => true]);
}

// ── Helpers ───────────────────────────────────────────────────────

function json_out(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

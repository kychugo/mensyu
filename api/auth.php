<?php
/**
 * api/auth.php — Registration, login, logout
 *
 * POST actions: register | login | logout
 * Returns JSON: { success: bool, message: string }
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action     = trim($_POST['action'] ?? '');
$csrf_token = $_POST['csrf_token'] ?? '';

// logout does not need CSRF for simplicity (session destroy)
if ($action !== 'logout' && !csrf_token_verify($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

match ($action) {
    'register' => auth_handle_register(),
    'login'    => auth_handle_login(),
    'logout'   => auth_handle_logout(),
    default    => json_error('Unknown action'),
};

// ── handlers ──────────────────────────────────────────────────────

function auth_handle_register(): void {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    $result = auth_register($username, $password, $confirm);
    echo json_encode($result);
}

function auth_handle_login(): void {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = auth_login($username, $password);
    if ($result['success']) {
        $redirect = $_POST['redirect'] ?? '/';
        $result['redirect'] = $redirect;
    }
    echo json_encode($result);
}

function auth_handle_logout(): void {
    auth_logout();
    echo json_encode(['success' => true, 'redirect' => '/']);
}

// ── functions ─────────────────────────────────────────────────────

function auth_register(string $username, string $password, string $confirm): array {
    if (strlen($username) < 2 || strlen($username) > 50) {
        return ['success' => false, 'message' => '用戶名稱需為 2–50 字元'];
    }
    if (!preg_match('/^[\w\x{4e00}-\x{9fff}]+$/u', $username)) {
        return ['success' => false, 'message' => '用戶名稱只能包含字母、數字、漢字或底線'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => '密碼至少需要 6 個字元'];
    }
    if ($password !== $confirm) {
        return ['success' => false, 'message' => '兩次密碼不一致'];
    }

    try {
        $exists = db_query('SELECT id FROM users WHERE username = ?', [$username])->fetch();
        if ($exists) {
            return ['success' => false, 'message' => '此用戶名稱已被使用'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        db_query('INSERT INTO users (username, password_hash) VALUES (?, ?)', [$username, $hash]);

        $user = db_query('SELECT id FROM users WHERE username = ?', [$username])->fetch();
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $username;

        return ['success' => true, 'message' => '注冊成功！'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => '資料庫錯誤，請稍後再試'];
    }
}

function auth_login(string $username, string $password): array {
    if ($username === '' || $password === '') {
        return ['success' => false, 'message' => '請填寫用戶名稱和密碼'];
    }

    try {
        $user = db_query('SELECT id, username, password_hash FROM users WHERE username = ?', [$username])->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => '用戶名稱或密碼錯誤'];
        }

        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];

        return ['success' => true, 'message' => '登入成功！'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => '資料庫錯誤，請稍後再試'];
    }
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function json_error(string $msg): void {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

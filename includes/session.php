<?php
/**
 * includes/session.php — Session initialisation and helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

function session_check_auth(): bool {
    return !empty($_SESSION['user_id']);
}

function session_get_user(): ?array {
    if (!session_check_auth()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'is_admin' => !empty($_SESSION['is_admin']),
    ];
}

function session_is_admin(): bool {
    return !empty($_SESSION['is_admin']);
}

function session_require_admin(): void {
    if (!session_is_admin()) {
        header('Location: /');
        exit;
    }
}

function csrf_token_generate(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_token_verify(string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

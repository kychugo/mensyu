<?php
/**
 * includes/error_tracker.php — PHP error handler + usage tracking
 *
 * Include ONCE near the top of index.php (after db.php is loaded).
 * Registers a set_error_handler / set_exception_handler that writes to the
 * error_logs table.  Also exposes error_log_db() for explicit logging.
 */

// ── Public helper ─────────────────────────────────────────────────────────────

define('ERROR_LOG_MAX_RECORDS', 2000);

function error_log_db(
    string  $level,
    string  $message,
    ?array  $context   = null,
    ?string $url       = null,
    ?int    $user_id   = null
): void {
    try {
        $ip  = get_real_ip();
        $ua  = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $url = $url ?? ($_SERVER['REQUEST_URI'] ?? null);

        db_query(
            'INSERT INTO error_logs
             (error_level, message, context, url, user_id, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                strtoupper($level),
                mb_substr($message, 0, 5000),
                $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
                $url ? mb_substr($url, 0, 500) : null,
                $user_id,
                $ip,
                $ua ? mb_substr($ua, 0, 500) : null,
            ]
        );

        // Keep at most ERROR_LOG_MAX_RECORDS error records
        db_query('DELETE FROM error_logs WHERE id NOT IN (
            SELECT id FROM (SELECT id FROM error_logs ORDER BY created_at DESC LIMIT ' . ERROR_LOG_MAX_RECORDS . ') t
        )');
    } catch (Throwable $e) {
        // Never let error logging itself crash the app
    }
}

// ── Usage tracking ────────────────────────────────────────────────────────────

function usage_track(string $page, string $action = 'view', ?int $user_id = null): void {
    try {
        db_query(
            'INSERT INTO usage_stats (user_id, ip_address, page, action) VALUES (?, ?, ?, ?)',
            [$user_id, get_real_ip(), mb_substr($page, 0, 100), mb_substr($action, 0, 100)]
        );
    } catch (Throwable $e) {}
}

// ── IP helper (used by geo_guard and error tracking) ─────────────────────────

function get_real_ip(): string {
    foreach ([
        'HTTP_CF_CONNECTING_IP',   // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

// ── PHP error / exception handlers ───────────────────────────────────────────

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    // Only capture warnings and above (skip E_NOTICE, E_DEPRECATED, etc.)
    if (!($errno & (E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING))) {
        return false; // let PHP handle notices
    }

    $level_map = [
        E_ERROR       => 'ERROR',   E_USER_ERROR   => 'ERROR',
        E_WARNING     => 'WARNING', E_USER_WARNING => 'WARNING',
        E_PARSE       => 'PARSE',
    ];
    $level = $level_map[$errno] ?? 'PHP';

    $msg  = "$errstr";
    $file = str_replace(dirname(__DIR__), '', $errfile); // relative path
    error_log_db($level, $msg, ['file' => $file, 'line' => $errline, 'errno' => $errno]);
    return false; // let PHP keep its default behaviour
});

set_exception_handler(function (Throwable $e): void {
    $file = str_replace(dirname(__DIR__), '', $e->getFile());
    error_log_db('EXCEPTION', $e->getMessage(), [
        'class' => get_class($e),
        'file'  => $file,
        'line'  => $e->getLine(),
        'trace' => mb_substr($e->getTraceAsString(), 0, 2000),
    ]);
});

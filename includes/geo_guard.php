<?php
/**
 * includes/geo_guard.php — Restrict access to Hong Kong IPs; block proxies/VPNs.
 *
 * Uses ip-api.com (free, no key) with DB-based caching (24 h TTL).
 * If the GeoIP API is unreachable the request is ALLOWED THROUGH (fail-open)
 * so the site never becomes inaccessible due to an external dependency.
 *
 * Admin users always bypass the geo guard.
 * The guard only activates when app_settings.geo_guard_enabled = '1'.
 */

define('GEO_CACHE_TTL_SECONDS', 86400); // 24 hours

function geo_guard(): void {
    // ── 1. Check if feature is enabled ───────────────────────────────
    try {
        $enabled = db_query(
            "SELECT setting_value FROM app_settings WHERE setting_key='geo_guard_enabled'"
        )->fetchColumn();
    } catch (Throwable $e) {
        return; // DB not ready yet — allow through
    }

    if ($enabled !== '1') {
        return;
    }

    // ── 2. Admin bypass ───────────────────────────────────────────────
    if (!empty($_SESSION['is_admin'])) {
        return;
    }

    // ── 3. Private / loopback IPs always allowed ──────────────────────
    $ip = get_real_ip();
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return; // private / loopback (local dev) — allow
    }

    // ── 4. Check DB cache ─────────────────────────────────────────────
    try {
        $cached = db_query(
            "SELECT country_code, is_proxy, cached_at FROM geo_cache WHERE ip_address = ?",
            [$ip]
        )->fetch();

        if ($cached) {
            $age = time() - strtotime($cached['cached_at']);
            if ($age < GEO_CACHE_TTL_SECONDS) {
                geo_apply_block($ip, $cached['country_code'], (bool)$cached['is_proxy']);
                return;
            }
        }
    } catch (Throwable $e) {
        return; // DB error — fail-open
    }

    // ── 5. Query ip-api.com ───────────────────────────────────────────
    $result = geo_lookup_ip($ip);
    if ($result === null) {
        return; // API unavailable — fail-open
    }

    // ── 6. Store in cache ─────────────────────────────────────────────
    try {
        db_query(
            "REPLACE INTO geo_cache (ip_address, country_code, is_proxy, cached_at)
             VALUES (?, ?, ?, NOW())",
            [$ip, $result['countryCode'], $result['isProxy'] ? 1 : 0]
        );
    } catch (Throwable $e) {}

    geo_apply_block($ip, $result['countryCode'], $result['isProxy']);
}

/**
 * Decide whether to block this request and show a 403 page.
 */
function geo_apply_block(string $ip, ?string $country, bool $is_proxy): void {
    $block_reason = null;

    if ($is_proxy) {
        $block_reason = '偵測到代理伺服器或 VPN，此平台不允許透過 VPN 訪問。';
    } elseif ($country !== 'HK') {
        $block_reason = '此平台僅供香港用戶使用。';
    }

    if ($block_reason !== null) {
        // Log attempt
        try {
            error_log_db('GEO_BLOCK', "Blocked IP: $ip (country: $country, proxy: " . ($is_proxy ? '1' : '0') . ")", [
                'ip' => $ip, 'country' => $country, 'proxy' => $is_proxy,
            ]);
        } catch (Throwable $e) {}

        geo_block_page($block_reason);
    }
}

/**
 * Query ip-api.com for IP information.
 * Returns ['countryCode' => 'HK', 'isProxy' => false] or null on failure.
 */
function geo_lookup_ip(string $ip): ?array {
    $url = "http://ip-api.com/json/{$ip}?fields=status,countryCode,proxy";
    $ctx = stream_context_create(['http' => [
        'timeout'        => 5,
        'ignore_errors'  => true,
        'method'         => 'GET',
    ]]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;

    $json = json_decode($raw, true);
    if (!$json || ($json['status'] ?? '') !== 'success') return null;

    return [
        'countryCode' => $json['countryCode'] ?? null,
        'isProxy'     => !empty($json['proxy']),
    ];
}

/**
 * Show the geo-block HTML page and exit.
 */
function geo_block_page(string $reason): void {
    http_response_code(403);
    echo <<<HTML
<!DOCTYPE html>
<html lang="zh-HK">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>訪問受限 — 文樞 Mensyu</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-amber-50 flex items-center justify-center min-h-screen p-8">
  <div class="text-center max-w-md">
    <div class="text-6xl mb-4">🔒</div>
    <h1 class="text-2xl font-bold text-gray-800 mb-3">訪問受限</h1>
    <p class="text-gray-600 mb-6">{$reason}</p>
    <p class="text-sm text-gray-400">文樞 Mensyu · DSE 文言文學習平台</p>
  </div>
</body>
</html>
HTML;
    exit;
}

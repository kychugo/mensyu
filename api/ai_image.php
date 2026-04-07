<?php
/**
 * api/ai_image.php — Image AI proxy (server-side)
 *
 * POST: { author, mood }
 * Returns JSON: { success, url }
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?: $_POST;
$author = $body['author'] ?? 'sushe';
$mood   = $body['mood']   ?? 'peaceful, contemplative';

$url = ai_image_generate($author, $mood);

if ($url === null) {
    echo json_encode(['success' => false, 'url' => null]);
    exit;
}

echo json_encode(['success' => true, 'url' => $url]);

// ── functions ─────────────────────────────────────────────────────

function ai_image_generate(string $author, string $mood): ?string {
    $names = ['sushe' => 'Su Shi Song dynasty', 'hanyu' => 'Han Yu Tang dynasty'];
    $name  = $names[$author] ?? 'ancient Chinese scholar';

    $prompt = "Ancient Chinese ink wash painting, {$name} era aesthetics, " .
              "{$mood}, traditional landscape, poetry atmosphere, " .
              "8K resolution, no text, no watermark, no modern elements";

    // Read endpoint and models from DB once per request, fall back to compiled-in defaults
    static $settingsLoaded = false;
    static $endpoint       = AI_IMAGE_ENDPOINT;
    static $models         = AI_IMAGE_MODELS;

    if (!$settingsLoaded) {
        try {
            $ep = db_query("SELECT setting_value FROM app_settings WHERE setting_key='ai_image_endpoint'")->fetchColumn();
            if ($ep && $ep !== '') $endpoint = $ep;
            $mj = db_query("SELECT setting_value FROM app_settings WHERE setting_key='ai_image_models'")->fetchColumn();
            $m  = json_decode($mj ?: '[]', true);
            if (!empty($m)) $models = $m;
        } catch (Throwable $e) {
            // keep compiled-in defaults
        }
        $settingsLoaded = true;
    }

    foreach ($models as $model) {
        $url = ai_image_request($prompt, $model, $endpoint);
        if ($url !== null) return $url;
    }
    return null;
}

function ai_image_request(string $prompt, string $model, string $endpoint = AI_IMAGE_ENDPOINT): ?string {
    $encoded  = rawurlencode($prompt);
    $url      = $endpoint . $encoded
        . '?model='   . urlencode($model)
        . '&key='     . urlencode(AI_SECRET_KEY)
        . '&width=512&height=512&nologo=true';

    $ctx = stream_context_create(['http' => ['timeout' => 30, 'ignore_errors' => true]]);
    $response = @file_get_contents($url, false, $ctx);

    // The API returns the image directly; check Content-Type header
    foreach ($http_response_header ?? [] as $h) {
        if (stripos($h, 'Content-Type: image/') !== false) {
            // It worked — return the request URL as the image src
            return $url;
        }
    }
    // Some models return JSON with url
    if ($response !== false) {
        $json = json_decode($response, true);
        if (!empty($json['url'])) return $json['url'];
    }
    return null;
}

<?php
/**
 * api/ai_image.php — Image AI proxy (server-side)
 *
 * POST: { author, mood }
 * Returns JSON: { success, url }
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/ai.php';

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

    foreach (AI_IMAGE_MODELS as $model) {
        $url = ai_image_request($prompt, $model);
        if ($url !== null) return $url;
    }
    return null;
}

function ai_image_request(string $prompt, string $model): ?string {
    $encoded  = rawurlencode($prompt);
    $endpoint = AI_IMAGE_ENDPOINT . $encoded
        . '?model='   . urlencode($model)
        . '&key='     . urlencode(AI_SECRET_KEY)
        . '&width=512&height=512&nologo=true';

    $ctx = stream_context_create(['http' => ['timeout' => 30, 'ignore_errors' => true]]);
    $response = @file_get_contents($endpoint, false, $ctx);

    // The API returns the image directly; check Content-Type header
    foreach ($http_response_header ?? [] as $h) {
        if (stripos($h, 'Content-Type: image/') !== false) {
            // It worked — return the request URL as the image src
            return $endpoint;
        }
    }
    // Some models return JSON with url
    if ($response !== false) {
        $json = json_decode($response, true);
        if (!empty($json['url'])) return $json['url'];
    }
    return null;
}

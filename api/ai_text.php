<?php
/**
 * api/ai_text.php — Text AI proxy (server-side, protects secret key)
 *
 * POST body (JSON or form): { action, payload }
 * actions: translate | quiz | annotate | pairs | post | comment
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
$action = $body['action'] ?? '';

$result = match ($action) {
    'translate' => ai_text_translate($body['text'] ?? ''),
    'quiz'      => ai_text_quiz($body['text'] ?? ''),
    'annotate'  => ai_text_annotate($body['word'] ?? '', $body['sentence'] ?? ''),
    'pairs'     => ai_text_pairs($body['text'] ?? '', (int)($body['count'] ?? 6)),
    'post'      => ai_text_post($body['author'] ?? '', $body['past'] ?? ''),
    'comment'   => ai_text_comment($body['author'] ?? '', $body['post'] ?? '', $body['past'] ?? ''),
    default     => null,
};

if ($result === null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'AI 暫時無法回應，請稍後再試']);
    exit;
}

echo json_encode(['success' => true, 'data' => $result]);

// ── Core call ─────────────────────────────────────────────────────

/**
 * Try Pollinations first (all configured models), then fall back to
 * DeepSeek official API using each stored key in turn.
 */
function ai_text_call(array $messages): ?string {
    // Read endpoint and models from DB settings once per request, fall back to compiled-in defaults
    static $settingsLoaded = false;
    static $endpoint       = AI_TEXT_ENDPOINT;
    static $models         = AI_TEXT_MODELS;

    if (!$settingsLoaded) {
        try {
            $ep = db_query("SELECT setting_value FROM app_settings WHERE setting_key='ai_text_endpoint'")->fetchColumn();
            if ($ep && $ep !== '') $endpoint = $ep;
            $mj = db_query("SELECT setting_value FROM app_settings WHERE setting_key='ai_text_models'")->fetchColumn();
            $m  = json_decode($mj ?: '[]', true);
            if (!empty($m)) $models = $m;
        } catch (Throwable $e) {
            // keep compiled-in defaults
        }
        $settingsLoaded = true;
    }

    // 1. Try each Pollinations model
    foreach ($models as $model) {
        $result = ai_text_request($endpoint, $model, $messages);
        if ($result !== null) return $result;
    }
    // 2. Fallback: DeepSeek official API
    return ai_deepseek_call($messages);
}

function ai_text_request(string $endpoint, string $model, array $messages): ?string {
    $payload = json_encode([
        'model'    => $model,
        'messages' => $messages,
        'seed'     => rand(1, 99999),
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", [
                'Content-Type: application/json',
                'Authorization: Bearer ' . AI_SECRET_KEY,
            ]),
            'content' => $payload,
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($endpoint, false, $ctx);
    if ($response === false) return null;

    $json = json_decode($response, true);
    return $json['choices'][0]['message']['content'] ?? null;
}

/**
 * DeepSeek official API fallback.
 * Reads API keys from app_settings.deepseek_api_keys (JSON array).
 * Auto-switches to next key on failure.
 */
function ai_deepseek_call(array $messages): ?string {
    try {
        $keysJson = db_query(
            "SELECT setting_value FROM app_settings WHERE setting_key='deepseek_api_keys'"
        )->fetchColumn();
        $keys = json_decode($keysJson ?: '[]', true);
    } catch (Throwable $e) {
        return null;
    }

    if (empty($keys)) return null;

    foreach ($keys as $key) {
        $key = trim($key);
        if ($key === '') continue;
        $result = ai_deepseek_request($key, $messages);
        if ($result !== null) return $result;
    }
    return null;
}

function ai_deepseek_request(string $apiKey, array $messages): ?string {
    $payload = json_encode([
        'model'    => 'deepseek-chat',
        'messages' => $messages,
        'stream'   => false,
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ]),
            'content' => $payload,
            'timeout' => 30,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents('https://api.deepseek.com/chat/completions', false, $ctx);
    if ($response === false) return null;

    $json = json_decode($response, true);
    // DeepSeek returns same OpenAI-compatible format
    return $json['choices'][0]['message']['content'] ?? null;
}

// ── JSON parse helpers ────────────────────────────────────────────

function ai_parse_json(string $text): mixed {
    $text = trim($text);
    // strip markdown code fences
    $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
    $text = preg_replace('/\s*```$/', '', $text);

    $parsed = json_decode($text, true);
    if ($parsed !== null) return $parsed;

    // try extracting first [...] or {...} block
    if (preg_match('/(\[.*\]|\{.*\})/s', $text, $m)) {
        return json_decode($m[1], true);
    }
    return null;
}

// ── Action implementations ────────────────────────────────────────

function ai_text_translate(string $text): ?string {
    if (trim($text) === '') return null;
    // Prompt instructs the AI to produce three labelled sections per sentence group:
    //   原文 (original text) | 語譯 (modern Chinese translation, sentence by sentence)
    //   逐字解釋 (character-by-character gloss, bold for common classical words)
    $messages = [
        ['role' => 'system', 'content' =>
            "請將以下文言文逐字翻譯並解釋(直譯，不要意譯)，格式要求：\n\n" .
            "原文：\n[顯示原文句子]\n\n" .
            "語譯：\n[顯示完整句子翻譯]\n\n" .
            "逐字解釋：\n[對每個文字進行解釋，格式為\"字：解釋\"，常見文言字詞用**粗體**標示，切勿解釋標點符號]\n\n" .
            "要求：\n" .
            "1. 為每一句(以，。？!：； 作為分隔)進行語譯\n" .
            "2. 如果該行有常見文言字詞，請為該字及其詞解粗體\n" .
            "3. 用中文繁體字顯示所有內容\n" .
            "4. 不要提供思考過程，用<think></think>中間內容\n" .
            "5. 保持嚴格的格式，使用標題和清晰的分段\n" .
            "6. 不要在任何地方使用多餘的星號(*)\n" .
            "7. 不要使用任何裝飾性符號或分隔線\n" .
            "8. 確保每部分都有明確的標題（原文、語譯、逐字解釋）\n" .
            "9. 逐字解釋只解釋文字字符，不解釋標點符號如，。？!等\n\n" .
            "必須注意以下要求，務必跟從：\n" .
            "為每一句(以，。？!：； 作為分隔)進行語譯，嚴禁整句進行語譯。"],
        ['role' => 'user', 'content' => $text],
    ];
    return ai_text_call($messages);
}

function ai_text_quiz(string $text): ?array {
    if (trim($text) === '') return null;
    $messages = [
        ['role' => 'system', 'content' =>
            '請基於以下文言文內容生成5道中文繁體選擇題，用來測試學習者的理解。' .
            '格式（JSON陣列）：[{"question":"問題","options":["A","B","C","D"],"answer":0,"explanation":"解析"}]' .
            '只回傳JSON，不要其他文字。'],
        ['role' => 'user', 'content' => $text],
    ];
    $raw = ai_text_call($messages);
    if ($raw === null) return null;
    return ai_parse_json($raw);
}

function ai_text_annotate(string $word, string $sentence): ?array {
    if ($word === '') return null;
    $messages = [
        ['role' => 'system', 'content' =>
            '請解釋文言文中的字詞在句子中的意思。' .
            '格式（JSON）：{"word":"字詞","meaning":"現代中文解釋（15字以內）","example":"例句（可選）"}' .
            '只回傳JSON，不要其他文字。'],
        ['role' => 'user', 'content' => "字詞：「{$word}」\n句子：{$sentence}"],
    ];
    $raw = ai_text_call($messages);
    if ($raw === null) return null;
    return ai_parse_json($raw);
}

function ai_text_pairs(string $text, int $count = 6): ?array {
    if (trim($text) === '') return null;
    $count = max(4, min(12, $count));
    $messages = [
        ['role' => 'system', 'content' =>
            "請從以下文言文段落中提取 {$count} 個重要字詞及其現代語譯，用作配對遊戲（字詞↔語譯）。" .
            '格式（JSON陣列）：[{"classical":"文言字詞","modern":"現代語譯（5字以內）"}]' .
            '每對字詞必須不同，只回傳JSON。'],
        ['role' => 'user', 'content' => $text],
    ];
    $raw = ai_text_call($messages);
    if ($raw === null) return null;
    return ai_parse_json($raw);
}

function ai_text_post(string $author, string $past = ''): ?string {
    $personas = ai_personas();
    $p = $personas[$author] ?? $personas['sushe'];
    $messages = [
        ['role' => 'system', 'content' =>
            "你現在是{$p['name']}，以{$p['personality']}的性格，使用{$p['style']}的文風，" .
            "撰寫一則古人社交媒體貼文（約80-120字）。\n" .
            "請確保內容新穎，與以下歷史內容的主題、觀點、用字或具體表達不重複：\n{$past}\n" .
            "使用繁體中文，適當融入粵語元素，但內容主要用字需要文言文，" .
            "確保語氣自然且符合角色性格，內容不要太過離地，要年輕及近代化，" .
            "用現代中學生易懂的文言混合粵語引用自身作品化解負面情緒保持豁達本色" .
            "並帶入生活化比喻，內容不要包含「tag(#)」或任何「註」"],
        ['role' => 'user', 'content' => '請發一則貼文。'],
    ];
    return ai_text_call($messages);
}

function ai_text_comment(string $author, string $post, string $past = ''): ?string {
    $personas = ai_personas();
    $p = $personas[$author] ?? $personas['sushe'];
    $messages = [
        ['role' => 'system', 'content' =>
            "你現在是{$p['name']}，以{$p['personality']}的性格，使用{$p['style']}的文風，" .
            "對以下貼文進行留言（約30-50字）：\n{$post}\n" .
            "請確保留言內容新穎，與以下歷史內容不重複：\n{$past}\n" .
            "請以繁體中文回應，適當融入粵語元素，但內容主要用字需要文言文，" .
            "語氣需符合角色性格並與貼文內容相關，內容不要包含「tag(#)」或任何「註」"],
        ['role' => 'user', 'content' => '請留言。'],
    ];
    return ai_text_call($messages);
}

function ai_personas(): array {
    return [
        'sushe' => [
            'name'        => '蘇軾',
            'personality' => '豁達樂觀、幽默風趣',
            'style'       => '豪放灑脫、意境深遠',
        ],
        'hanyu' => [
            'name'        => '韓愈',
            'personality' => '剛正不阿、憂國憂民',
            'style'       => '古樸嚴謹、言簡意賅',
        ],
    ];
}

<?php
/**
 * api/cron_post.php — Auto-post for ancient authors (triggered by cron every 2 hours)
 *
 * Usage: php api/cron_post.php  OR  curl https://…/api/cron_post.php?cron_key=SECRET
 */

// CLI or verified cron key only
$cron_key = getenv('CRON_KEY') ?: 'mensyu_cron_2026';
if (PHP_SAPI !== 'cli') {
    if (($_GET['cron_key'] ?? '') !== $cron_key) {
        http_response_code(403);
        exit('Forbidden');
    }
}

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ai.php';

// Randomly pick an author
$authors = ['sushe', 'hanyu'];
$author  = $authors[array_rand($authors)];

// Fetch past content to avoid repetition
$past_posts = [];
try {
    $rows = db_query(
        'SELECT content FROM teahouse_posts WHERE author_persona = ? ORDER BY created_at DESC LIMIT 5',
        [$author]
    )->fetchAll();
    $past_posts = array_column($rows, 'content');
} catch (PDOException $e) {}

$past = implode("\n---\n", $past_posts);

// Generate AI post text
$content = ai_cron_generate_post($author, $past);
if ($content === null) {
    exit("AI returned null for author {$author}\n");
}

// Generate image (optional, silent fail)
$image_url = ai_cron_generate_image($author, $content);

// Insert post
try {
    $personas = ['sushe' => '蘇軾', 'hanyu' => '韓愈'];
    db_query(
        'INSERT INTO teahouse_posts (user_id, author_persona, username, content, image_url, post_type)
         VALUES (NULL, ?, ?, ?, ?, "ai")',
        [$author, $personas[$author], $content, $image_url]
    );
    // Enforce limit
    $count = (int)db_query('SELECT COUNT(*) FROM teahouse_posts')->fetchColumn();
    if ($count > 50) {
        db_query('DELETE FROM teahouse_posts ORDER BY created_at ASC LIMIT ?', [$count - 50]);
    }
    echo "Posted as {$author}: " . mb_substr($content, 0, 40) . "…\n";
} catch (PDOException $e) {
    exit('DB error: ' . $e->getMessage() . "\n");
}

// ── helpers ───────────────────────────────────────────────────────

function ai_cron_generate_post(string $author, string $past): ?string {
    require_once __DIR__ . '/ai_text.php';
    return ai_text_post($author, $past);
}

function ai_cron_generate_image(string $author, string $content): ?string {
    $moods = [
        'sushe' => 'serene river landscape, moonlight, philosophical, peaceful',
        'hanyu' => 'Tang dynasty court, mountains, determined scholar, ink brushwork',
    ];
    $mood = $moods[$author] ?? 'ancient Chinese atmosphere';

    require_once __DIR__ . '/ai_image.php';
    return ai_image_generate($author, $mood);
}

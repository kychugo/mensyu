<?php
/**
 * api/cron_post.php — Auto-post for ancient authors.
 *
 * Can be:
 *   (a) Included by index.php — calls cron_run() directly (no cmd needed)
 *   (b) Hit via HTTP with ?cron_key=VALUE (CRON_KEY env var must be set)
 *       e.g. https://yoursite.com/api/cron_post.php?cron_key=SECRET
 */

// Prevent direct HTTP access without a valid key
// (When included from index.php, PHP_SAPI will still be 'apache2handler' etc.,
//  but $GLOBALS['_cron_included'] will be set by index.php before requiring this file.)
if (!defined('_CRON_INTERNAL') && PHP_SAPI !== 'cli') {
    $cron_key = getenv('CRON_KEY');
    if (!$cron_key || !hash_equals($cron_key, $_GET['cron_key'] ?? '')) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// Only define functions once (this file may be require_once'd multiple times)
if (!function_exists('cron_run')) {

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ai.php';

function cron_run(): void {
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

    $past    = implode("\n---\n", $past_posts);
    $content = cron_generate_post($author, $past);
    if ($content === null) return;

    $image_url = cron_generate_image($author, $content);

    try {
        $personas = ['sushe' => '蘇軾', 'hanyu' => '韓愈'];
        db_query(
            'INSERT INTO teahouse_posts (user_id, author_persona, username, content, image_url, post_type)
             VALUES (NULL, ?, ?, ?, ?, "ai")',
            [$author, $personas[$author], $content, $image_url]
        );
        // Enforce 50-post cap, cleaning up orphaned comments
        $count = (int)db_query('SELECT COUNT(*) FROM teahouse_posts')->fetchColumn();
        if ($count > 50) {
            $to_delete = $count - 50;
            $old_ids   = db_query(
                'SELECT id FROM teahouse_posts ORDER BY created_at ASC LIMIT ?',
                [$to_delete]
            )->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($old_ids)) {
                $placeholders = implode(',', array_fill(0, count($old_ids), '?'));
                db_query("DELETE FROM teahouse_comments WHERE post_id IN ($placeholders)", $old_ids);
            }
            db_query('DELETE FROM teahouse_posts ORDER BY created_at ASC LIMIT ?', [$to_delete]);
        }
    } catch (PDOException $e) {}
}

function cron_generate_post(string $author, string $past): ?string {
    require_once __DIR__ . '/ai_text.php';
    return ai_text_post($author, $past);
}

function cron_generate_image(string $author, string $content): ?string {
    $moods = [
        'sushe' => 'serene river landscape, moonlight, philosophical, peaceful',
        'hanyu' => 'Tang dynasty court, mountains, determined scholar, ink brushwork',
    ];
    require_once __DIR__ . '/ai_image.php';
    return ai_image_generate($author, $moods[$author] ?? 'ancient Chinese atmosphere');
}

} // end if (!function_exists('cron_run'))

// ── When called via HTTP directly, execute immediately ───────────
if (!defined('_CRON_INTERNAL')) {
    cron_run();
    echo "Cron completed.\n";
}

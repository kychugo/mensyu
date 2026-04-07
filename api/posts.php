<?php
/**
 * api/posts.php — Teahouse post CRUD
 *
 * GET  ?action=list&page=N           → paginated posts
 * POST action=add                    → add user post
 * POST action=comment                → add comment
 * GET  ?action=comments&post_id=N    → get comments for post
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $method === 'GET' ? ($_GET['action'] ?? 'list') : ($_POST['action'] ?? '');

if ($action === 'list') {
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    try {
        $posts = db_query(
            'SELECT id, user_id, author_persona, username, content, image_url, post_type, created_at
             FROM teahouse_posts ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        )->fetchAll();

        echo json_encode(['success' => true, 'data' => $posts]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
    exit;
}

if ($action === 'comments') {
    $post_id = (int)($_GET['post_id'] ?? 0);
    try {
        $comments = db_query(
            'SELECT id, user_id, username, content, is_ai, created_at
             FROM teahouse_comments WHERE post_id = ? ORDER BY created_at ASC',
            [$post_id]
        )->fetchAll();
        echo json_encode(['success' => true, 'data' => $comments]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
    exit;
}

// ── Write actions require login ───────────────────────────────────
if (!session_check_auth()) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

$user = session_get_user();

if ($action === 'add') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!csrf_token_verify($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    $content = trim($_POST['content'] ?? '');
    if (strlen($content) < 2 || strlen($content) > 500) {
        echo json_encode(['success' => false, 'message' => '內容需為 2–500 字']);
        exit;
    }
    try {
        db_query(
            'INSERT INTO teahouse_posts (user_id, username, content, post_type) VALUES (?, ?, ?, "user")',
            [$user['id'], $user['username'], $content]
        );
        // Enforce max 50 posts
        teahouse_enforce_limit();
        // Grant 💬 古今對話 achievement: combined posts + comments >= 10
        $posts_cnt = (int)db_query(
            'SELECT COUNT(*) FROM teahouse_posts WHERE user_id = ?',
            [$user['id']]
        )->fetchColumn();
        $comments_cnt = (int)db_query(
            'SELECT COUNT(*) FROM teahouse_comments WHERE user_id = ?',
            [$user['id']]
        )->fetchColumn();
        if ($posts_cnt + $comments_cnt >= 10) {
            db_query('INSERT IGNORE INTO achievements (user_id, badge_id) VALUES (?, ?)', [$user['id'], 'social_10']);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
    exit;
}

if ($action === 'comment') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!csrf_token_verify($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    $post_id = (int)($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    if ($post_id <= 0 || strlen($content) < 1 || strlen($content) > 300) {
        echo json_encode(['success' => false, 'message' => '留言內容無效']);
        exit;
    }
    try {
        db_query(
            'INSERT INTO teahouse_comments (post_id, user_id, username, content, is_ai) VALUES (?, ?, ?, ?, 0)',
            [$post_id, $user['id'], $user['username'], $content]
        );
        // Grant achievement check: combined posts + comments >= 10
        $posts_cnt = (int)db_query(
            'SELECT COUNT(*) FROM teahouse_posts WHERE user_id = ?',
            [$user['id']]
        )->fetchColumn();
        $comments_cnt = (int)db_query(
            'SELECT COUNT(*) FROM teahouse_comments WHERE user_id = ?',
            [$user['id']]
        )->fetchColumn();
        if ($posts_cnt + $comments_cnt >= 10) {
            db_query('INSERT IGNORE INTO achievements (user_id, badge_id) VALUES (?, ?)', [$user['id'], 'social_10']);
        }
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);

function teahouse_enforce_limit(): void {
    try {
        $count = (int)db_query('SELECT COUNT(*) FROM teahouse_posts')->fetchColumn();
        if ($count > 50) {
            $to_delete = $count - 50;
            // Delete orphaned comments for the oldest posts first
            $old_ids = db_query(
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

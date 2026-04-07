<?php
/**
 * api/essays.php — DSE中文12篇指定文言範文 list / content
 *
 * GET ?action=list                   → array of {id,title,author,dynasty,genre,type,category}
 * GET ?action=get&id=N               → full essay object
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

$file = __DIR__ . '/../data/essays.json';
if (!file_exists($file)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'essays.json not found']);
    exit;
}

$essays = json_decode(file_get_contents($file), true) ?? [];

if ($action === 'list') {
    $list = array_map(fn($e) => [
        'id'       => $e['id'],
        'title'    => $e['title'],
        'author'   => $e['author'],
        'dynasty'  => $e['dynasty'],
        'genre'    => $e['genre'],
        'type'     => $e['type'] ?? '',
        'category' => $e['category'] ?? '',
    ], $essays);
    echo json_encode(['success' => true, 'data' => $list]);
    exit;
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    foreach ($essays as $e) {
        if ($e['id'] === $id) {
            // Track read for logged-in users and grant read_10 achievement
            if ($id > 0 && session_check_auth()) {
                $user_id = session_get_user()['id'];
                try {
                    // Only record once per day per essay to avoid inflating the distinct count
                    $page_key = 'essay_' . $id;
                    // Check if this user has already viewed this essay today to avoid duplicate counts
                    $already = (int)db_query(
                        "SELECT COUNT(*) FROM usage_stats WHERE user_id=? AND page=? AND action='essay_view' AND created_at >= DATE(NOW())",
                        [$user_id, $page_key]
                    )->fetchColumn();
                    if ($already === 0) {
                        db_query(
                            "INSERT INTO usage_stats (user_id, page, action) VALUES (?, ?, 'essay_view')",
                            [$user_id, $page_key]
                        );
                    }
                    // Grant read_10 if user has read at least 10 distinct essays
                    $distinct = (int)db_query(
                        "SELECT COUNT(DISTINCT page) FROM usage_stats WHERE user_id=? AND action='essay_view'",
                        [$user_id]
                    )->fetchColumn();
                    if ($distinct >= 10) {
                        db_query(
                            'INSERT IGNORE INTO achievements (user_id, badge_id) VALUES (?, ?)',
                            [$user_id, 'read_10']
                        );
                    }
                } catch (Throwable $e2) {}
            }
            echo json_encode(['success' => true, 'data' => $e]);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Essay not found']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);

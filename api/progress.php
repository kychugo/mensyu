<?php
/**
 * api/progress.php — User learning progress CRUD
 *
 * GET  ?action=get&author_id=sushe   → progress rows for current user
 * POST action=save                   → upsert progress row
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $method === 'GET' ? ($_GET['action'] ?? '') : ($_POST['action'] ?? '');

if (!session_check_auth()) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

$user_id = session_get_user()['id'];

if ($action === 'get') {
    $author_id = $_GET['author_id'] ?? null;
    try {
        if ($author_id) {
            $rows = db_query(
                'SELECT author_id, level, stars FROM user_progress WHERE user_id = ? AND author_id = ?',
                [$user_id, $author_id]
            )->fetchAll();
        } else {
            $rows = db_query(
                'SELECT author_id, level, stars FROM user_progress WHERE user_id = ?',
                [$user_id]
            )->fetchAll();
        }
        echo json_encode(['success' => true, 'data' => $rows]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
    exit;
}

if ($action === 'save' && $method === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!csrf_token_verify($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $author_id = $_POST['author_id'] ?? '';
    $level     = (int)($_POST['level'] ?? 0);
    $stars     = (int)($_POST['stars'] ?? 0);

    if (!in_array($author_id, ['sushe', 'hanyu'], true) || $level < 1 || $level > 4 || $stars < 0 || $stars > 3) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    try {
        db_query(
            'INSERT INTO user_progress (user_id, author_id, level, stars)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE stars = GREATEST(stars, VALUES(stars)), updated_at = CURRENT_TIMESTAMP',
            [$user_id, $author_id, $level, $stars]
        );
        progress_check_achievements($user_id, $author_id, $level, $stars);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);

// ── helpers ───────────────────────────────────────────────────────

function progress_check_achievements(int $user_id, string $author_id, int $level, int $stars): void {
    // 🔰 初入文場 — complete any level 1
    if ($level === 1 && $stars >= 1) {
        achievement_grant($user_id, 'first_level');
    }

    // 🏆 文樞大師 — all 4 levels for both authors
    try {
        $total = db_query(
            'SELECT COUNT(*) as cnt FROM user_progress WHERE user_id = ? AND stars >= 1',
            [$user_id]
        )->fetchColumn();
        if ((int)$total >= 8) {
            achievement_grant($user_id, 'master');
        }
    } catch (PDOException $e) {}
}

function achievement_grant(int $user_id, string $badge_id): void {
    try {
        db_query(
            'INSERT IGNORE INTO achievements (user_id, badge_id) VALUES (?, ?)',
            [$user_id, $badge_id]
        );
    } catch (PDOException $e) {}
}

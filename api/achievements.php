<?php
/**
 * api/achievements.php — Achievement list for current user
 *
 * GET  ?action=list          → user's earned achievements
 * POST action=game_complete  → record a game completion and grant badge when threshold reached
 *   params: game (breakout|matching), csrf_token
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

// ── POST: record game completion ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!session_check_auth()) {
        echo json_encode(['success' => false, 'message' => '請先登入']);
        exit;
    }

    $csrf = $_POST['csrf_token'] ?? '';
    if (!csrf_token_verify($csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action !== 'game_complete') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }

    $game = $_POST['game'] ?? '';
    if (!in_array($game, ['breakout', 'matching'], true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid game']);
        exit;
    }

    $user_id    = session_get_user()['id'];
    $action_key = 'game_' . $game . '_complete';
    // Thresholds: breakout needs 3 perfect clears, matching needs 5 completions
    $thresholds = ['breakout' => 3, 'matching' => 5];
    $badges     = ['breakout' => 'breakout_3', 'matching' => 'matching_5'];

    try {
        // Record this completion
        db_query(
            'INSERT INTO usage_stats (user_id, page, action) VALUES (?, ?, ?)',
            [$user_id, 'games', $action_key]
        );

        // Count total completions for this user + game
        $count = (int)db_query(
            'SELECT COUNT(*) FROM usage_stats WHERE user_id = ? AND action = ?',
            [$user_id, $action_key]
        )->fetchColumn();

        // Grant achievement when threshold is reached
        $badge_granted = null;
        if ($count >= $thresholds[$game]) {
            db_query(
                'INSERT IGNORE INTO achievements (user_id, badge_id) VALUES (?, ?)',
                [$user_id, $badges[$game]]
            );
            $badge_granted = $badges[$game];
        }

        echo json_encode(['success' => true, 'count' => $count, 'badge' => $badge_granted]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
    }
    exit;
}

// ── GET: list achievements ────────────────────────────────────────
$ALL_BADGES = [
    'first_level' => ['icon' => '🔰', 'name' => '初入文場',   'desc' => '完成任一作者第 1 關'],
    'read_10'     => ['icon' => '📖', 'name' => '博覽古籍',   'desc' => '閱讀 10 篇不同文本'],
    'breakout_3'  => ['icon' => '⚔️',  'name' => '磚場勇士',   'desc' => '打磚塊遊戲完美通關 3 次'],
    'matching_5'  => ['icon' => '🃏', 'name' => '古今配對',   'desc' => '文言配對遊戲完成 5 次'],
    'master'      => ['icon' => '🏆', 'name' => '文樞大師',   'desc' => '完成所有作者所有關卡'],
    'social_10'   => ['icon' => '💬', 'name' => '古今對話',   'desc' => '在茶館發文或留言 10 次'],
];

if (!session_check_auth()) {
    echo json_encode(['success' => true, 'data' => [], 'all' => $ALL_BADGES]);
    exit;
}

$user_id = session_get_user()['id'];

try {
    $earned_rows = db_query(
        'SELECT badge_id, earned_at FROM achievements WHERE user_id = ?',
        [$user_id]
    )->fetchAll();

    $earned = [];
    foreach ($earned_rows as $row) {
        $earned[$row['badge_id']] = $row['earned_at'];
    }

    echo json_encode(['success' => true, 'data' => $earned, 'all' => $ALL_BADGES]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}

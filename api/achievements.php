<?php
/**
 * api/achievements.php — Achievement list for current user
 *
 * GET ?action=list  → user's earned achievements
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

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

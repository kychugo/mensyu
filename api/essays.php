<?php
/**
 * api/essays.php — DSE essay list / content
 *
 * GET ?action=list                   → array of {id,title,author,dynasty,genre}
 * GET ?action=get&id=N               → full essay object
 */

require_once __DIR__ . '/../includes/session.php';

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
        'id'     => $e['id'],
        'title'  => $e['title'],
        'author' => $e['author'],
        'dynasty'=> $e['dynasty'],
        'genre'  => $e['genre'],
    ], $essays);
    echo json_encode(['success' => true, 'data' => $list]);
    exit;
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    foreach ($essays as $e) {
        if ($e['id'] === $id) {
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

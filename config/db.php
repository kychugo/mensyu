<?php
/**
 * config/db.php — MySQL PDO connection
 */

// Prefer environment variables; fall back to local config file.
// In production: set DB_HOST, DB_NAME, DB_USER, DB_PASS via server env or a
// gitignored config/local.php that defines these constants before this file.
$_local_cfg = __DIR__ . '/local.php';
if (file_exists($_local_cfg)) include_once $_local_cfg;

defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: 'sql111.infinityfree.com');
defined('DB_PORT') || define('DB_PORT', getenv('DB_PORT') ?: '3306');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'if0_41581260_mensyu');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'if0_41581260');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') ?: '');

$_db_instance = null;

function db_connect(): PDO {
    global $_db_instance;
    if ($_db_instance !== null) {
        return $_db_instance;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $_db_instance = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $_db_instance;
}

function db_query(string $sql, array $params = []): PDOStatement {
    $pdo  = db_connect();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_escape(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

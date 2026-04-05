<?php
/**
 * config/db.php — MySQL PDO connection
 */

define('DB_HOST', 'sql111.infinityfree.com');
define('DB_PORT', '3306');
define('DB_NAME', 'if0_41581260_mensyu');
define('DB_USER', 'if0_41581260');
define('DB_PASS', 'hfy23whc');

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

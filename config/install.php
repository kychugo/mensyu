<?php
/**
 * config/install.php — Auto-create all database tables on first run.
 * Called automatically by db_connect() when the .installed flag is absent.
 * All statements use CREATE TABLE IF NOT EXISTS and try/catch ALTER TABLE
 * so they are fully idempotent — safe to run on every deployment.
 */

function db_install(): void {
    $pdo = db_connect();

    $tables = [

        // ── Users ─────────────────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS users (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            username      VARCHAR(50)  NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            is_admin      TINYINT(1)   NOT NULL DEFAULT 0,
            is_banned     TINYINT(1)   NOT NULL DEFAULT 0,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // ── Learning progress ─────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS user_progress (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT         NOT NULL,
            author_id  VARCHAR(20) NOT NULL,
            level      INT         NOT NULL,
            stars      INT         NOT NULL DEFAULT 0,
            updated_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_progress (user_id, author_id, level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // ── Achievements ──────────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS achievements (
            id        INT AUTO_INCREMENT PRIMARY KEY,
            user_id   INT         NOT NULL,
            badge_id  VARCHAR(50) NOT NULL,
            earned_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_badge (user_id, badge_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // ── Teahouse posts ────────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS teahouse_posts (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            user_id        INT          NULL,
            author_persona VARCHAR(20)  NULL,
            username       VARCHAR(50)  NOT NULL,
            content        TEXT         NOT NULL,
            image_url      TEXT         NULL,
            post_type      ENUM('user','ai') DEFAULT 'user',
            created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // ── Teahouse comments ─────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS teahouse_comments (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            post_id    INT         NOT NULL,
            user_id    INT         NULL,
            username   VARCHAR(50) NOT NULL,
            content    TEXT        NOT NULL,
            is_ai      TINYINT(1)  DEFAULT 0,
            created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // ── Error / bug logs ──────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS error_logs (
            id          INT AUTO_INCREMENT  PRIMARY KEY,
            error_level VARCHAR(20)  NOT NULL DEFAULT 'ERROR',
            message     TEXT         NOT NULL,
            context     TEXT         NULL,
            url         VARCHAR(500) NULL,
            user_id     INT          NULL,
            ip_address  VARCHAR(45)  NULL,
            user_agent  TEXT         NULL,
            created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_level     (error_level),
            INDEX idx_created   (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // ── Usage / page-view stats ───────────────────────────────────
        "CREATE TABLE IF NOT EXISTS usage_stats (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT         NULL,
            ip_address VARCHAR(45) NULL,
            page       VARCHAR(100) NULL,
            action     VARCHAR(100) NULL,
            created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_page    (page),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // ── GeoIP cache ───────────────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS geo_cache (
            ip_address   VARCHAR(45) NOT NULL PRIMARY KEY,
            country_code VARCHAR(5)  NULL,
            is_proxy     TINYINT(1)  DEFAULT 0,
            cached_at    TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // ── Application settings ──────────────────────────────────────
        "CREATE TABLE IF NOT EXISTS app_settings (
            setting_key   VARCHAR(50) NOT NULL PRIMARY KEY,
            setting_value TEXT        NOT NULL,
            updated_at    TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }

    // ── Safe column upgrades (idempotent ALTER TABLE) ─────────────────
    $alters = [
        "ALTER TABLE users ADD COLUMN is_admin  TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE users ADD COLUMN is_banned TINYINT(1) NOT NULL DEFAULT 0",
    ];
    foreach ($alters as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) { /* column exists — ignore */ }
    }

    // ── Default settings (INSERT IGNORE) ─────────────────────────────
    $defaults = [
        ['geo_guard_enabled',   '0'],   // off by default — admin can enable
        ['cron_last_run',       '0'],
        ['cron_interval_hours', '2'],
        ['platform_name',       '文樞 Mensyu'],
        ['deepseek_api_keys',   '[]'],  // JSON array of DeepSeek API keys
    ];
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (?, ?)'
    );
    foreach ($defaults as [$k, $v]) {
        $stmt->execute([$k, $v]);
    }
}

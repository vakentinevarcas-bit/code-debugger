<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'codequest';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$DB_NAME`");

    $datadirRow = $pdo->query("SHOW VARIABLES LIKE 'datadir'")->fetch();
    $dataDir = rtrim($datadirRow['Value'] ?? '', '/\\');
    $schemaDir = $dataDir !== '' ? ($dataDir . DIRECTORY_SEPARATOR . $DB_NAME) : '';
    $removeOrphanedFiles = function (string $table) use ($schemaDir): void {
        if ($schemaDir === '' || !is_dir($schemaDir)) {
            return;
        }
        $base = rtrim($schemaDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $table;
        foreach (['.ibd', '.frm', '.cfg', '.opt', '.mrg', '.MYD', '.MYI'] as $ext) {
            $file = $base . $ext;
            if (is_file($file)) {
                @unlink($file);
            }
        }
    };
    $ensureTable = function (string $table, string $ddl) use ($pdo, $removeOrphanedFiles): void {
        try {
            $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        } catch (PDOException $e) {
            try {
                $pdo->exec("ALTER TABLE `$table` DISCARD TABLESPACE");
            } catch (PDOException $discardErr) {
            }
            try {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
            } catch (PDOException $dropErr) {
                try {
                    $pdo->exec("ALTER TABLE `$table` FORCE");
                } catch (PDOException $forceErr) {
                }
            }
            $removeOrphanedFiles($table);
        }
        $pdo->exec("CREATE TABLE IF NOT EXISTS `$table` ($ddl) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    };

    $ensureTable('weekly_updates', "
        id INT AUTO_INCREMENT PRIMARY KEY,
        week VARCHAR(50) NOT NULL DEFAULT 'Week 1',
        title VARCHAR(255) NOT NULL,
        date_badge VARCHAR(100) DEFAULT '',
        tag VARCHAR(20) DEFAULT 'new',
        items MEDIUMTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ");

    // ── system_updates table ──
    $ensureTable('system_updates', "
        id INT AUTO_INCREMENT PRIMARY KEY,
        module VARCHAR(255) NOT NULL,
        description TEXT,
        status VARCHAR(20) DEFAULT 'Live',
        image VARCHAR(255) DEFAULT NULL,
        updated_at VARCHAR(120) DEFAULT 'Recently updated',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ");

// ── Seed weekly updates if empty ──
    $weeklyCount = (int) $pdo->query("SELECT COUNT(*) FROM weekly_updates")->fetchColumn();
    if ($weeklyCount === 0) {
        $weeks = [
        ];
        $stmt = $pdo->prepare("INSERT INTO weekly_updates (week, title, date_badge, tag, items) VALUES (?, ?, ?, ?, ?)");
        foreach ($weeks as $w) {
            $stmt->execute([$w['week'], $w['title'], $w['date_badge'], $w['tag'], json_encode($w['items'])]);
        }
    }

// ── users table (uses the same robust self-healing helper) ──
    $ensureTable('users', "
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        score INT DEFAULT 0,
        status VARCHAR(20) DEFAULT 'Active',
        role VARCHAR(20) DEFAULT 'user',
        last_seen DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ");

    // ── Ensure last_seen column exists (for existing installs) ──
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_seen DATETIME NULL AFTER role");
    } catch (PDOException $e) {
        // Column likely already exists — ignore.
    }

    // ── admin table (dedicated admin accounts for the admin login) ──
    $ensureTable('admin', "
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        score INT DEFAULT 0,
        status VARCHAR(20) DEFAULT 'Active',
        role VARCHAR(20) DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ");

    // ── Seed default admin account (email: admin@codequest.com / pass: admin123) ──
    $adminExists = (int) $pdo->query("SELECT COUNT(*) FROM admin WHERE email='admin@codequest.com'")->fetchColumn();
    if ($adminExists === 0) {
        $stmt = $pdo->prepare("INSERT INTO admin (first_name, last_name, email, password, role) VALUES ('Admin', 'CodeQuest', 'admin@codequest.com', ?, 'admin')");
        $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
    }

    // ── game_scores table (stores each finished run's points) ──
    $ensureTable('game_scores', "
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        score INT DEFAULT 0,
        tokens INT DEFAULT 0,
        fragments INT DEFAULT 0,
        questions INT DEFAULT 0,
        errors INT DEFAULT 0,
        level INT DEFAULT 0,
        won TINYINT(1) DEFAULT 0,
        rank_label VARCHAR(50) DEFAULT '',
        played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_score (score)
    ");

    // ── Update users.score with the cumulative best across all games ──
    $pdo->exec("UPDATE users u
                LEFT JOIN (
                    SELECT user_id, MAX(score) AS best FROM game_scores GROUP BY user_id
                ) gs ON gs.user_id = u.id
                SET u.score = COALESCE(gs.best, u.score)");

// ── Seed system updates if empty ──
    $systemCount = (int) $pdo->query("SELECT COUNT(*) FROM system_updates")->fetchColumn();
    if ($systemCount === 0) {
        $systems = [
            
        ];
        $stmt = $pdo->prepare("INSERT INTO system_updates (module, description, status, updated_at) VALUES (?, ?, ?, ?)");
        foreach ($systems as $s) {
            $stmt->execute([$s['module'], $s['description'], $s['status'], $s['updated_at']]);
        }
    }

} catch (PDOException $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

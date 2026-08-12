
--  Admin login: admin@codequest.com  /  admin123
CREATE DATABASE IF NOT EXISTS `codequest`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `codequest`;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `score` INT DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'Active',
    `role` VARCHAR(20) DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `role`)
VALUES ('Admin', 'CodeQuest', 'admin@codequest.com', '$2y$10$DZdufQ6kNDVBJMwCFlHEKeqlWwLa4/1WYbdTIZMieFv1pLVOdZs5i', 'admin');

DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `score` INT DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'Active',
    `role` VARCHAR(20) DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admin` (`first_name`, `last_name`, `email`, `password`, `role`)
VALUES ('Admin', 'CodeQuest', 'admin@codequest.com', '$2y$10$DZdufQ6kNDVBJMwCFlHEKeqlWwLa4/1WYbdTIZMieFv1pLVOdZs5i', 'admin');

DROP TABLE IF EXISTS `game_scores`;
CREATE TABLE `game_scores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `score` INT DEFAULT 0,
    `tokens` INT DEFAULT 0,
    `fragments` INT DEFAULT 0,
    `questions` INT DEFAULT 0,
    `errors` INT DEFAULT 0,
    `level` INT DEFAULT 0,
    `won` TINYINT(1) DEFAULT 0,
    `rank_label` VARCHAR(50) DEFAULT '',
    `played_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `weekly_updates`;
CREATE TABLE `weekly_updates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `week` VARCHAR(50) NOT NULL DEFAULT 'Week 1',
    `title` VARCHAR(255) NOT NULL,
    `date_badge` VARCHAR(100) DEFAULT '',
    `tag` VARCHAR(20) DEFAULT 'new',
    `items` MEDIUMTEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `weekly_updates` (`week`, `title`, `date_badge`, `tag`, `items`) VALUES
('Week 1', '🚀 New Features', 'Aug 1 – 7', 'new', '[{"tag":"new","text":"<strong>Power-Up System:</strong> Collect the star to become invincible for 8 seconds — enemies crumble on contact!"},{"tag":"update","text":"<strong>Question Block rework:</strong> Coding challenges now show line numbers for easier debugging."},{"tag":"fix","text":"<strong>Timer pause:</strong> Game timer now correctly pauses during challenge input."},{"tag":"new","text":"<strong>Sound effects:</strong> Added 8-bit jump and coin collect SFX (toggle in settings)."}]'),
('Week 2', '⚙️ Performance & Polish', 'Aug 8 – 14', 'update', '[{"tag":"update","text":"<strong>Rendering overhaul:</strong> Smoother 60fps on all devices — reduced draw calls by 40%."},{"tag":"fix","text":"<strong>Goomba collision:</strong> Fixed inconsistent stomp detection when jumping from angles."},{"tag":"new","text":"<strong>Particle effects:</strong> Coin bursts and block-break particles now pop with colour."},{"tag":"update","text":"<strong>Mobile controls:</strong> Added on-screen buttons for touch devices."}]'),
('Week 3', '🧩 Level Design', 'Aug 15 – 21', 'new', '[{"tag":"new","text":"<strong>World 4 – Frost Caverns:</strong> 6 new levels with ice physics and sliding puzzles."},{"tag":"update","text":"<strong>Checkpoint system:</strong> Flags now save progress mid-level — no more full restarts!"},{"tag":"fix","text":"<strong>Gate trigger:</strong> Fixed rare issue where gate wouldn''t appear after all blocks solved."},{"tag":"new","text":"<strong>Hidden warp zones:</strong> Discover secret pipes to skip ahead (or get lost!)."}]'),
('Week 4', '🛠️ Community & QoL', 'Aug 22 – 28', 'update', '[{"tag":"update","text":"<strong>Leaderboards:</strong> Global rankings now show top 100 players by score and clear time."},{"tag":"new","text":"<strong>Daily challenge:</strong> A new coding puzzle every day with exclusive rewards."},{"tag":"fix","text":"<strong>Input lag:</strong> Reduced key repeat delay for more responsive jumping."},{"tag":"update","text":"<strong>Accessibility:</strong> Added colourblind mode and adjustable text size."}]');

DROP TABLE IF EXISTS `system_updates`;
CREATE TABLE `system_updates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `module` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `status` VARCHAR(20) DEFAULT 'Live',
    `image` VARCHAR(255) DEFAULT NULL,
    `updated_at` VARCHAR(120) DEFAULT 'Recently updated',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_updates` (`module`, `description`, `status`, `updated_at`) VALUES
('⚡ Core Engine', '<p><strong>Physics tick rate</strong> increased from 60 to 120 Hz for ultra-smooth movement. Collision detection now uses continuous sweeping to prevent tunneling.</p><ul><li>Improved memory pooling</li><li>Reduced GC pressure</li><li>Shader compilation cached</li></ul>', 'Live', 'Updated 2 days ago'),
('🌐 Networking', '<p><strong>WebSocket reconnection</strong> is now fully automatic with exponential backoff. Latency compensation added for multiplayer sync.</p><ul><li>Packet compression enabled</li><li>Reduced handshake overhead</li><li>Fallback to HTTP/2</li></ul>', 'Live', 'Updated 5 days ago'),
('🔒 Security', '<p><strong>OAuth2 token rotation</strong> implemented for all API calls. Session timeout reduced to 30 minutes for improved safety.</p><ul><li>CSRF protection added</li><li>Input sanitisation hardened</li><li>Audit logging extended</li></ul>', 'Live', 'Updated 1 day ago'),
('💾 Storage', '<p><strong>IndexedDB sharding</strong> now splits save data across multiple stores for faster load/save. Compression ratio improved to 68%.</p><ul><li>Auto-vacuum enabled</li><li>Corruption recovery</li><li>Export/import saves</li></ul>', 'Live', 'Updated 3 days ago'),
('🎨 UI Framework', '<p><strong>Component library</strong> migrated to a virtual DOM for faster re-renders. Theme system now supports dark/light/auto.</p><ul><li>Reduced bundle size by 22%</li><li>Lazy-loading routes</li><li>Accessibility ARIA labels</li></ul>', 'Live', 'Updated 6 days ago'),
('📊 Analytics', '<p><strong>Real-time dashboard</strong> now streams player metrics with 5s latency. Custom alerts for error spikes.</p><ul><li>Retention cohort analysis</li><li>Funnel visualisation</li><li>Export to CSV/JSON</li></ul>', 'Live', 'Updated 4 days ago');

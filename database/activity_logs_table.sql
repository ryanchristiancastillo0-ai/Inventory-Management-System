-- ==========================================================
-- ACTIVITY LOGS (audit trail)
-- One row per user action recorded by ActivityLogService.
-- Fixes: PDOException "Table 'inventory_db.activity_logs'
-- doesn't exist" on the admin Activity Logs page.
-- Matches the definition in inventory_db.sql exactly.
-- ==========================================================

CREATE TABLE `activity_logs` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int UNSIGNED DEFAULT NULL,
    `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_activity_user` (`user_id`),
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

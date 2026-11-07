-- ==========================================================
-- NOTIFICATIONS (email delivery log)
-- One row per email notification the system attempts to send.
-- Type: 'order' | 'stock_movement' | 'report' | 'password_reset'
-- (matches what the settings toggle "Account, order and security
-- updates" gates).
-- ==========================================================

CREATE TABLE `notifications` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int UNSIGNED NOT NULL,
    `type` ENUM('order','stock_movement','report','password_reset') COLLATE utf8mb4_unicode_ci NOT NULL,
    `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
    `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `recipient_email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
    `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brevo',
    `provider_message_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `status` ENUM('pending','sent','failed','skipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
    `error_message` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `sent_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user` (`user_id`, `id`),
    KEY `idx_notifications_type` (`user_id`, `type`),
    KEY `idx_notifications_status` (`status`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `notifications`
    ADD CONSTRAINT `fk_notifications_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE;

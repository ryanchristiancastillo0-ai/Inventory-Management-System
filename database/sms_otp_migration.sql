-- ==========================================================
-- SMS OTP AUTHENTICATION MIGRATION
-- Adds phone number support and OTP challenge storage
-- ==========================================================

-- 1. Add phone columns to users table
ALTER TABLE `users`
    ADD COLUMN `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `email`,
    ADD COLUMN `phone_verified_at` timestamp NULL DEFAULT NULL AFTER `phone`;

-- Add unique index on phone.
-- MySQL AUTOMATICALLY allows multiple NULL values in a unique index,
-- so users without a phone number don't conflict. (No partial index
-- syntax -- MySQL does not support `CREATE INDEX ... WHERE ...`.)
CREATE UNIQUE INDEX `unique_phone` ON `users` (`phone`);

-- 2. Create SMS OTP challenges table (similar to password_resets)
CREATE TABLE `sms_otp_challenges` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int UNSIGNED NOT NULL,
    `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
    -- bcrypt hash of the 6-digit OTP code (never store raw code)
    `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `attempts` tinyint UNSIGNED NOT NULL DEFAULT 0,
    `verified_at` timestamp NULL DEFAULT NULL,
    `used_at` timestamp NULL DEFAULT NULL,
    `expires_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sms_otp_user` (`user_id`),
    KEY `idx_sms_otp_phone` (`phone`),
    KEY `idx_sms_otp_expires` (`expires_at`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `sms_otp_challenges`
    ADD CONSTRAINT `fk_sms_otp_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE;

-- 3. Create index for cleanup of expired challenges
CREATE INDEX `idx_sms_otp_cleanup` ON `sms_otp_challenges` (`expires_at`, `used_at`);
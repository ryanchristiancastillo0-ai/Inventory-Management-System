-- ==========================================================
-- PASSWORD RESETS
-- Stores one row per "forgot password" request:
--   - the hashed 6-digit code emailed to the user
--   - after the code is verified, the hashed one-time token
--     returned to the frontend to authorize the final reset
--
-- Lifecycle: pending -> verified (code OK, token issued)
--            -> used (password changed)  | invalidated
--            by reused request / too many wrong attempts /
--            expiry (codes & tokens expire).
-- ==========================================================

CREATE TABLE `password_resets` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int UNSIGNED NOT NULL,
    `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
    -- bcrypt hash of the 6-digit code (never store the raw code)
    `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    -- sha-256 hash of the one-time reset token (never store raw token)
    `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `attempts` tinyint UNSIGNED NOT NULL DEFAULT 0,
    `verified_at` timestamp NULL DEFAULT NULL,
    `used_at` timestamp NULL DEFAULT NULL,
    `code_expires_at` timestamp NULL DEFAULT NULL,
    `token_expires_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_password_resets_email` (`email`, `id`),
    KEY `idx_password_resets_user` (`user_id`),
    KEY `idx_password_resets_token` (`token_hash`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `password_resets`
    ADD CONSTRAINT `fk_password_resets_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE;

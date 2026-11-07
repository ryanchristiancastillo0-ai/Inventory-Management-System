<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class SettingsRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // GET USER SETTINGS
    // ==========================================

    public function getSettingsByUserId(
        int $userId
    ): array|false {
        $stmt = $this->db->prepare(
            "SELECT
                user_id,
                theme,
                email_notifications
             FROM user_settings
             WHERE user_id = :user_id"
        );

        $stmt->execute([
            ":user_id" => $userId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // SAVE SETTINGS
    // ==========================================

    public function saveSettings(
        int $userId,
        string $theme,
        bool $emailNotifications
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO user_settings
                (user_id, theme, email_notifications)
             VALUES
                (:user_id, :theme, :email_notifications)
             ON DUPLICATE KEY UPDATE
                theme = VALUES(theme),
                email_notifications = VALUES(email_notifications)"
        );

        return $stmt->execute([
            ":user_id" => $userId,
            ":theme" => $theme,
            ":email_notifications" => (int) $emailNotifications
        ]);
    }

    // ==========================================
    // GET USER ACCOUNT
    // ==========================================

    public function getAccountById(int $userId): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT
                id,
                username,
                email,
                role,
                created_at
             FROM users
             WHERE id = :id"
        );

        $stmt->execute([
            ":id" => $userId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // FIND EMAIL OWNER
    // ==========================================

    public function findUserByEmail(
        string $email
    ): array|false {
        $stmt = $this->db->prepare(
            "SELECT id
             FROM users
             WHERE email = :email"
        );

        $stmt->execute([
            ":email" => $email
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // UPDATE PROFILE
    // ==========================================

    public function updateProfile(
        int $userId,
        string $username,
        string $email
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET
                username = :username,
                email = :email
             WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $userId,
            ":username" => $username,
            ":email" => $email
        ]);
    }

    // ==========================================
    // GET PASSWORD HASH
    // ==========================================

    public function getPasswordHash(
        int $userId
    ): string|false {
        $stmt = $this->db->prepare(
            "SELECT password_hash
             FROM users
             WHERE id = :id"
        );

        $stmt->execute([
            ":id" => $userId
        ]);

        return $stmt->fetchColumn();
    }

    // ==========================================
    // UPDATE PASSWORD
    // ==========================================

    public function updatePassword(
        int $userId,
        string $passwordHash
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE users
             SET password_hash = :password_hash
             WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $userId,
            ":password_hash" => $passwordHash
        ]);
    }
}

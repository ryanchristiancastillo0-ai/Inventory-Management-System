<?php

namespace App\Repositories;

use App\Config\Database;
use PDO;

class AuthRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // =============================
    // USERS
    // =============================

    public function findUserByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = :email LIMIT 1"
        );

        $stmt->execute([
            ':email' => $email
        ]);

        return $stmt->fetch();
    }

    // Matches the username unique key, which is case-insensitive
    // (utf8mb4_unicode_ci), same as the table's UNIQUE index.
    public function findUserByUsername(string $username): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE username = :username LIMIT 1"
        );

        $stmt->execute([
            ':username' => $username
        ]);

        return $stmt->fetch();
    }

    public function findUserById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE id = :id LIMIT 1"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch();
    }

    public function createUser(
        string $username,
        string $email,
        string $passwordHash
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO users
             (username, email, password_hash, role)
             VALUES (:username, :email, :password_hash, 'USER')"
        );

        return $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => $passwordHash
        ]);
    }

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
            ':password_hash' => $passwordHash,
            ':id' => $userId
        ]);
    }

    // =============================
    // GOOGLE OAUTH
    // =============================

    public function findGoogleAccount(
        string $googleId
    ): array|false {
        $stmt = $this->db->prepare(
            "SELECT u.*
             FROM users u
             INNER JOIN user_oauth_accounts oauth
                ON oauth.user_id = u.id
             WHERE oauth.provider = 'GOOGLE'
             AND oauth.provider_user_id = :google_id
             LIMIT 1"
        );

        $stmt->execute([
            ':google_id' => $googleId
        ]);

        return $stmt->fetch();
    }

    public function linkGoogleAccount(
        int $userId,
        string $googleId
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO user_oauth_accounts
             (user_id, provider, provider_user_id)
             VALUES (:user_id, 'GOOGLE', :google_id)"
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':google_id' => $googleId
        ]);
    }

    // =============================
    // PASSKEYS / WEBAUTHN
    // =============================

    public function savePasskey(
        int $userId,
        string $credentialId,
        string $publicKey,
        int $signCount = 0
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO user_passkeys
             (user_id, credential_id, public_key, sign_count)
             VALUES
             (:user_id, :credential_id, :public_key, :sign_count)"
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':credential_id' => $credentialId,
            ':public_key' => $publicKey,
            ':sign_count' => $signCount
        ]);
    }

    public function findPasskey(
        string $credentialId
    ): array|false {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM user_passkeys
             WHERE credential_id = :credential_id
             LIMIT 1"
        );

        $stmt->execute([
            ':credential_id' => $credentialId
        ]);

        return $stmt->fetch();
    }

    public function getUserPasskeys(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM user_passkeys
             WHERE user_id = :user_id
             ORDER BY created_at DESC"
        );

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }

    public function updatePasskeyCounter(
        int $passkeyId,
        int $signCount
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE user_passkeys
             SET sign_count = :sign_count
             WHERE id = :id"
        );

        return $stmt->execute([
            ':sign_count' => $signCount,
            ':id' => $passkeyId
        ]);
    }

    public function deletePasskey(
        int $passkeyId,
        int $userId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM user_passkeys
             WHERE id = :id
             AND user_id = :user_id"
        );

        return $stmt->execute([
            ':id' => $passkeyId,
            ':user_id' => $userId
        ]);
    }
}

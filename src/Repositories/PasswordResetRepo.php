<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

/**
 * Persistence for the `password_resets` table:
 * one row per "forgot password" request — hashed 6-digit code,
 * then (after verification) the hashed one-time reset token.
 */
class PasswordResetRepo
{
    /** Maximum wrong attempts before a code is dead. */
    public const MAX_ATTEMPTS = 5;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // CREATE
    // ==========================================

    /**
     * Stores the hashed code for a reset request.
     *
     * Expiry is computed by MySQL (NOW() + INTERVAL) so the stored
     * expiry and every `> NOW()` comparison share one clock,
     * regardless of the PHP timezone.
     *
     * @return int Row id (0 when the insert failed).
     */
    public function create(
        int $userId,
        string $email,
        string $codeHash,
        int $codeTtlMinutes
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO password_resets
                (user_id, email, code_hash, code_expires_at)
             VALUES
                (:user_id, :email, :code_hash, DATE_ADD(NOW(), INTERVAL "
                . max(1, (int) $codeTtlMinutes) . " MINUTE))"
        );

        $ok = $stmt->execute([
            ":user_id" => $userId,
            ":email" => $email,
            ":code_hash" => $codeHash
        ]);

        return $ok ? (int) $this->db->lastInsertId() : 0;
    }

    // ==========================================
    // READ
    // ==========================================

    /**
     * The newest still-usable code row for an email:
     * not used, not expired and under the attempt limit.
     *
     * @return array<string, mixed>|false
     */
    public function getActiveByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM password_resets
             WHERE email = :email
               AND used_at IS NULL
               AND verified_at IS NULL
               AND code_expires_at > NOW()
               AND attempts < :max_attempts
             ORDER BY id DESC
             LIMIT 1"
        );

        $stmt->execute([
            ":email" => $email,
            ":max_attempts" => self::MAX_ATTEMPTS
        ]);

        return $stmt->fetch();
    }

    /**
     * Row matching a reset token (hashed). Only rows whose code
     * was verified, whose token is not expired and that have not
     * been used for a reset yet are accepted.
     *
     * @return array<string, mixed>|false
     */
    public function getByTokenHash(string $tokenHash): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM password_resets
             WHERE token_hash = :token_hash
               AND verified_at IS NOT NULL
               AND used_at IS NULL
               AND token_expires_at > NOW()
             LIMIT 1"
        );

        $stmt->execute([
            ":token_hash" => $tokenHash
        ]);

        return $stmt->fetch();
    }

    // ==========================================
    // UPDATE
    // ==========================================

    /** Bump wrong-attempt counter after a code mismatch. */
    public function bumpAttempts(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE password_resets
             SET attempts = attempts + 1
             WHERE id = :id"
        );

        return $stmt->execute([":id" => $id]);
    }

    /** Kills every still-pending code for an email (new request). */
    public function invalidatePending(string $email): void
    {
        $stmt = $this->db->prepare(
            "UPDATE password_resets
             SET used_at = NOW()
             WHERE email = :email
               AND used_at IS NULL
               AND verified_at IS NULL"
        );

        $stmt->execute([":email" => $email]);
    }

    /**
     * Code verified: record the hashed one-time token, its expiry
     * (MySQL clock, see create()) and when verification happened.
     * The row stops being usable as a code (verified rows are
     * excluded from getActiveByEmail).
     */
    public function markVerified(
        int $id,
        string $tokenHash,
        int $tokenTtlMinutes
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE password_resets
             SET verified_at = NOW(),
                 token_hash = :token_hash,
                 token_expires_at = DATE_ADD(NOW(), INTERVAL "
                 . max(1, (int) $tokenTtlMinutes) . " MINUTE)
             WHERE id = :id"
        );

        return $stmt->execute([
            ":token_hash" => $tokenHash,
            ":id" => $id
        ]);
    }

    /** Finalizes the flow: the token can no longer be reused. */
    public function markUsed(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE password_resets
             SET used_at = NOW()
             WHERE id = :id"
        );

        return $stmt->execute([":id" => $id]);
    }
}

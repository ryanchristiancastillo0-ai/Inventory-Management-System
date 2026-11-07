<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

/**
 * Repository for SMS OTP challenges.
 *
 * Stores bcrypt-hashed OTP codes with expiration and attempt tracking.
 * Never stores raw OTP codes.
 */
class SmsOtpRepo
{
    private PDO $db;

    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const OTP_TTL_MINUTES = 5;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Create a new OTP challenge.
     *
     * @param int    $userId   User ID
     * @param string $phone    Phone number in E.164 format (+639XXXXXXXXX)
     * @param string $codeHash bcrypt hash of the 6-digit OTP
     * @param int    $ttlMinutes Time-to-live in minutes (default 5)
     *
     * @return int The new challenge ID
     */
    public function create(int $userId, string $phone, string $codeHash, int $ttlMinutes = self::OTP_TTL_MINUTES): int
    {
        // Invalidate any pending challenges for this user/phone
        $this->invalidatePending($userId, $phone);

        $expiresAt = (new \DateTime())->modify("+{$ttlMinutes} minutes")->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO sms_otp_challenges
             (user_id, phone, code_hash, expires_at)
             VALUES (:user_id, :phone, :code_hash, :expires_at)"
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':phone' => $phone,
            ':code_hash' => $codeHash,
            ':expires_at' => $expiresAt,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get the active (unused, unexpired) challenge for a user/phone.
     */
    public function getActiveByUserAndPhone(int $userId, string $phone): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sms_otp_challenges
             WHERE user_id = :user_id
               AND phone = :phone
               AND used_at IS NULL
               AND verified_at IS NULL
               AND expires_at > NOW()
             ORDER BY created_at DESC
             LIMIT 1"
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':phone' => $phone,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get the active challenge by ID (for verification).
     */
    public function getActiveById(int $challengeId): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sms_otp_challenges
             WHERE id = :id
               AND used_at IS NULL
               AND verified_at IS NULL
               AND expires_at > NOW()
             LIMIT 1"
        );

        $stmt->execute([':id' => $challengeId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Increment attempt counter.
     */
    public function bumpAttempts(int $challengeId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sms_otp_challenges
             SET attempts = attempts + 1
             WHERE id = :id"
        );

        return $stmt->execute([':id' => $challengeId]);
    }

    /**
     * Mark challenge as verified (code was correct).
     */
    public function markVerified(int $challengeId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sms_otp_challenges
             SET verified_at = NOW()
             WHERE id = :id"
        );

        return $stmt->execute([':id' => $challengeId]);
    }

    /**
     * Mark challenge as used (login completed).
     */
    public function markUsed(int $challengeId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sms_otp_challenges
             SET used_at = NOW()
             WHERE id = :id"
        );

        return $stmt->execute([':id' => $challengeId]);
    }

    /**
     * Invalidate all pending challenges for a user/phone.
     * Called when a new OTP is requested or when one is verified.
     */
    public function invalidatePending(int $userId, string $phone): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sms_otp_challenges
             SET used_at = NOW()
             WHERE user_id = :user_id
               AND phone = :phone
               AND used_at IS NULL
               AND verified_at IS NULL"
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':phone' => $phone,
        ]);
    }

    /**
     * Check if a resend is allowed (cooldown period).
     *
     * @return array ['allowed' => bool, 'secondsRemaining' => int]
     */
    public function checkResendCooldown(int $userId, string $phone): array
    {
        $stmt = $this->db->prepare(
            "SELECT created_at FROM sms_otp_challenges
             WHERE user_id = :user_id
               AND phone = :phone
             ORDER BY created_at DESC
             LIMIT 1"
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':phone' => $phone,
        ]);

        $last = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$last) {
            return ['allowed' => true, 'secondsRemaining' => 0];
        }

        $createdAt = strtotime($last['created_at']);
        $elapsed = time() - $createdAt;
        $remaining = self::RESEND_COOLDOWN_SECONDS - $elapsed;

        if ($remaining <= 0) {
            return ['allowed' => true, 'secondsRemaining' => 0];
        }

        return ['allowed' => false, 'secondsRemaining' => $remaining];
    }

    /**
     * Get count of recent OTP requests for rate limiting.
     *
     * @param int $userId User ID
     * @param int $windowMinutes Time window in minutes
     * @param int $maxRequests Maximum allowed requests in window
     *
     * @return array ['allowed' => bool, 'count' => int, 'windowMinutes' => int]
     */
    public function checkRateLimit(int $userId, int $windowMinutes = 60, int $maxRequests = 10): array
    {
        $since = (new \DateTime())->modify("-{$windowMinutes} minutes")->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM sms_otp_challenges
             WHERE user_id = :user_id
               AND created_at >= :since"
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':since' => $since,
        ]);

        $count = (int) $stmt->fetchColumn();

        return [
            'allowed' => $count < $maxRequests,
            'count' => $count,
            'windowMinutes' => $windowMinutes,
            'maxRequests' => $maxRequests,
        ];
    }

    /**
     * Clean up expired challenges (can be run periodically).
     *
     * @return int Number of deleted rows
     */
    public function cleanupExpired(): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM sms_otp_challenges
             WHERE expires_at < NOW()
               AND (used_at IS NOT NULL OR verified_at IS NOT NULL)"
        );

        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Find user by verified phone number.
     *
     * @param string $phone Phone number in E.164 format
     * @return array|false User record or false if not found
     */
    public function findUserByVerifiedPhone(string $phone): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users
             WHERE phone = :phone
               AND phone_verified_at IS NOT NULL
             LIMIT 1"
        );

        $stmt->execute([':phone' => $phone]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update user's phone number and verification status.
     *
     * @param int    $userId User ID
     * @param string $phone  Phone number in E.164 format (or null to clear)
     * @param bool   $verified Whether the phone is verified
     *
     * @return bool
     */
    public function updateUserPhone(int $userId, ?string $phone, bool $verified = false): bool
    {
        $verifiedAt = $verified ? (new \DateTime())->format('Y-m-d H:i:s') : null;

        $stmt = $this->db->prepare(
            "UPDATE users
             SET phone = :phone,
                 phone_verified_at = :phone_verified_at
             WHERE id = :id"
        );

        return $stmt->execute([
            ':id' => $userId,
            ':phone' => $phone,
            ':phone_verified_at' => $verifiedAt,
        ]);
    }

    /**
     * Check if a phone number is already verified for another user.
     *
     * @param string $phone Phone number in E.164 format
     * @param int    $excludeUserId User ID to exclude from check
     * @return bool True if phone is already verified for another user
     */
    public function isPhoneVerifiedForAnotherUser(string $phone, int $excludeUserId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM users
             WHERE phone = :phone
               AND phone_verified_at IS NOT NULL
               AND id <> :exclude_id
             LIMIT 1"
        );

        $stmt->execute([
            ':phone' => $phone,
            ':exclude_id' => $excludeUserId,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
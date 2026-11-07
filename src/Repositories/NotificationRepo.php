<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

/**
 * Persistence for the `notifications` table:
 * a delivery log for every email notification the system
 * tries to send (account, order and security updates).
 */
class NotificationRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // CREATE
    // ==========================================

    /**
     * Inserts a pending notification row and returns its id.
     *
     * @param array{
     *     user_id: int,
     *     type: string,
     *     subject: string,
     *     recipient_email: string,
     *     provider?: string
     * } $data
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications
                (user_id, type, subject, recipient_email, provider, status)
             VALUES
                (:user_id, :type, :subject, :recipient_email, :provider, 'pending')"
        );

        $stmt->execute([
            ":user_id" => $data["user_id"],
            ":type" => $data["type"],
            ":subject" => $data["subject"],
            ":recipient_email" => $data["recipient_email"],
            ":provider" => $data["provider"] ?? "brevo"
        ]);

        return (int) $this->db->lastInsertId();
    }

    // ==========================================
    // UPDATE OUTCOME
    // ==========================================

    public function markSent(int $id, string $messageId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications
             SET status = 'sent',
                 provider_message_id = :message_id,
                 error_message = NULL,
                 sent_at = NOW()
             WHERE id = :id"
        );

        return $stmt->execute([
            ":message_id" => $messageId,
            ":id" => $id
        ]);
    }

    public function markFailed(int $id, string $error): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications
             SET status = 'failed',
                 error_message = :error
             WHERE id = :id"
        );

        return $stmt->execute([
            ":error" => mb_substr($error, 0, 1000),
            ":id" => $id
        ]);
    }

    /**
     * Skipped = user has email notifications disabled or
     * Brevo is not configured.
     */
    public function markSkipped(int $id, string $reason = ''): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications
             SET status = 'skipped',
                 error_message = :reason
             WHERE id = :id"
        );

        return $stmt->execute([
            ":reason" => mb_substr($reason, 0, 1000),
            ":id" => $id
        ]);
    }

    // ==========================================
    // READ
    // ==========================================

    public function getById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT
                id,
                user_id,
                type,
                channel,
                subject,
                recipient_email,
                provider,
                provider_message_id,
                status,
                error_message,
                created_at,
                sent_at
             FROM notifications
             WHERE id = :id"
        );

        $stmt->execute([":id" => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Latest notifications for one user (newest first).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByUserId(int $userId, int $limit = 50): array
    {
        $limit = max(1, min($limit, 100));

        $stmt = $this->db->prepare(
            "SELECT
                id,
                type,
                subject,
                recipient_email,
                status,
                error_message,
                created_at,
                sent_at
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY id DESC
             LIMIT {$limit}"
        );

        $stmt->execute([":user_id" => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

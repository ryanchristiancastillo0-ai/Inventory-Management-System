<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class ActivityLogRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // CREATE ACTIVITY LOG
    // ==========================================

    public function createLog(
        ?int $userId,
        string $action,
        string $description
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO activity_logs
                (user_id, action, description)
             VALUES
                (:user_id, :action, :description)"
        );

        $stmt->bindValue(
            ":user_id",
            $userId,
            $userId === null
                ? PDO::PARAM_NULL
                : PDO::PARAM_INT
        );

        $stmt->bindValue(
            ":action",
            $action,
            PDO::PARAM_STR
        );

        $stmt->bindValue(
            ":description",
            $description,
            PDO::PARAM_STR
        );

        return $stmt->execute();
    }

    // ==========================================
    // GET ALL LOGS (PAGINATED)
    // ==========================================

    public function getAllLogs(
        int $limit = 20,
        int $offset = 0
    ): array {
        $stmt = $this->db->prepare(
            "SELECT
                al.id,
                al.user_id,
                u.username,
                al.action,
                al.description,
                al.created_at
             FROM activity_logs AS al
             LEFT JOIN users AS u
                ON al.user_id = u.id
             ORDER BY al.created_at DESC, al.id DESC
             LIMIT :limit OFFSET :offset"
        );

        $stmt->bindValue(
            ":limit",
            $limit,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ":offset",
            $offset,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // FIND LOG BY ID
    // ==========================================

    public function getLogById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT
                al.id,
                al.user_id,
                u.username,
                al.action,
                al.description,
                al.created_at
             FROM activity_logs AS al
             LEFT JOIN users AS u
                ON al.user_id = u.id
             WHERE al.id = :id"
        );

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // GET LOGS BY USER
    // ==========================================

    public function getLogsByUserId(int $userId, ?int $limit = null): array
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM activity_logs
             WHERE user_id = :user_id
              ORDER BY created_at DESC, id DESC" . ($limit !== null ? ' LIMIT ' . max(1, min($limit, 100)) : '')
        );

        $stmt->execute([
            ":user_id" => $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // GET LOGS BY ACTION
    // ==========================================

    public function getLogsByAction(string $action): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                al.id,
                al.user_id,
                u.username,
                al.action,
                al.description,
                al.created_at
             FROM activity_logs AS al
             LEFT JOIN users AS u
                ON al.user_id = u.id
             WHERE al.action = :action
             ORDER BY al.created_at DESC, al.id DESC"
        );

        $stmt->execute([
            ":action" => $action
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // COUNT ALL LOGS
    // ==========================================

    public function countLogs(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM activity_logs"
        );

        return (int) $stmt->fetchColumn();
    }

    // ==========================================
    // COUNT TODAY'S LOGS
    // ==========================================

    public function countTodayLogs(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*)
             FROM activity_logs
             WHERE created_at >= CURDATE()
               AND created_at < CURDATE() + INTERVAL 1 DAY"
        );

        return (int) $stmt->fetchColumn();
    }

    // ==========================================
    // GET RECENT LOGS
    // ==========================================

    public function getRecentLogs(int $limit = 5): array
    {
        return $this->getAllLogs(
            max(1, min($limit, 100)),
            0
        );
    }
}

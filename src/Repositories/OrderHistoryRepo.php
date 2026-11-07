<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class OrderHistoryRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // GET ALL ORDER HISTORY
    // ==========================================

    public function getAllOrderHistory(): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                oh.*,
                o.order_number,
                u.username
             FROM order_history oh
             INNER JOIN orders o
                ON o.id = oh.order_id
             LEFT JOIN users u
                ON u.id = oh.user_id
             ORDER BY oh.created_at DESC, oh.id DESC"
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    // ==========================================
    // FIND HISTORY BY ID
    // ==========================================

    public function getOrderHistoryById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT
                oh.*,
                o.order_number,
                u.username
             FROM order_history oh
             INNER JOIN orders o
                ON o.id = oh.order_id
             LEFT JOIN users u
                ON u.id = oh.user_id
             WHERE oh.id = :id
             LIMIT 1"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch();
    }

    // ==========================================
    // GET HISTORY BY ORDER ID
    // ==========================================

    public function getHistoryByOrderId(int $orderId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                oh.*,
                o.order_number,
                u.username
             FROM order_history oh
             INNER JOIN orders o
                ON o.id = oh.order_id
             LEFT JOIN users u
                ON u.id = oh.user_id
             WHERE oh.order_id = :order_id
             ORDER BY oh.created_at DESC, oh.id DESC"
        );

        $stmt->execute([
            ':order_id' => $orderId
        ]);

        return $stmt->fetchAll();
    }

    // ==========================================
    // GET HISTORY BY USER ID
    // ==========================================

    public function getHistoryByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                oh.*,
                o.order_number,
                u.username
             FROM order_history oh
             INNER JOIN orders o
                ON o.id = oh.order_id
             LEFT JOIN users u
                ON u.id = oh.user_id
             WHERE oh.user_id = :user_id
             ORDER BY oh.created_at DESC, oh.id DESC"
        );

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }

    // ==========================================
    // GET HISTORY BY ACTION
    // ==========================================

    public function getHistoryByAction(string $action): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                oh.*,
                o.order_number,
                u.username
             FROM order_history oh
             INNER JOIN orders o
                ON o.id = oh.order_id
             LEFT JOIN users u
                ON u.id = oh.user_id
             WHERE oh.action = :action
             ORDER BY oh.created_at DESC, oh.id DESC"
        );

        $stmt->execute([
            ':action' => $action
        ]);

        return $stmt->fetchAll();
    }

    // ==========================================
    // CREATE ORDER HISTORY
    // ==========================================

    public function createOrderHistory(
        int $orderId,
        ?int $userId,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?string $remarks
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO order_history (
                order_id,
                user_id,
                action,
                old_status,
                new_status,
                remarks
             ) VALUES (
                :order_id,
                :user_id,
                :action,
                :old_status,
                :new_status,
                :remarks
             )"
        );

        return $stmt->execute([
            ':order_id' => $orderId,
            ':user_id' => $userId,
            ':action' => $action,
            ':old_status' => $oldStatus,
            ':new_status' => $newStatus,
            ':remarks' => $remarks
        ]);
    }

    // ==========================================
    // COUNT HISTORY BY ORDER
    // ==========================================

    public function countHistoryByOrderId(int $orderId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM order_history
             WHERE order_id = :order_id"
        );

        $stmt->execute([
            ':order_id' => $orderId
        ]);

        return (int) $stmt->fetchColumn();
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class OrderRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // GET ALL ORDERS WITH SUPPLIER DETAILS
    // ==========================================

    public function getAllOrders(): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                o.*,
                s.name AS supplier_name,
                u.username AS created_by
             FROM orders o
             LEFT JOIN suppliers s
                ON s.id = o.supplier_id
             LEFT JOIN users u
                ON u.id = o.user_id
             ORDER BY o.created_at DESC, o.id DESC"
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    // ==========================================
    // FIND ORDER BY ID
    // ==========================================

    public function getOrderById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT
                o.*,
                s.name AS supplier_name,
                u.username AS created_by
             FROM orders o
             LEFT JOIN suppliers s
                ON s.id = o.supplier_id
             LEFT JOIN users u
                ON u.id = o.user_id
             WHERE o.id = :id
             LIMIT 1"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch();
    }

    // ==========================================
    // FIND ORDER BY ORDER NUMBER
    // ==========================================

    public function findOrderByNumber(
        string $orderNumber
    ): array|false {
        $stmt = $this->db->prepare(
            "SELECT
                o.*,
                s.name AS supplier_name
             FROM orders o
             LEFT JOIN suppliers s
                ON s.id = o.supplier_id
             WHERE o.order_number = :order_number
             LIMIT 1"
        );

        $stmt->execute([
            ':order_number' => $orderNumber
        ]);

        return $stmt->fetch();
    }

    // ==========================================
    // GET ORDERS BY USER
    // ==========================================

    public function getOrdersByUserId(
        int $userId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT
                o.*,
                s.name AS supplier_name
             FROM orders o
             LEFT JOIN suppliers s
                ON s.id = o.supplier_id
             WHERE o.user_id = :user_id
             ORDER BY o.created_at DESC, o.id DESC"
        );

        $stmt->execute([
            ':user_id' => $userId
        ]);

        return $stmt->fetchAll();
    }

    // ==========================================
    // GET ORDERS BY SUPPLIER
    // ==========================================

    public function getOrdersBySupplierId(
        int $supplierId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT
                o.*,
                s.name AS supplier_name
             FROM orders o
             LEFT JOIN suppliers s
                ON s.id = o.supplier_id
             WHERE o.supplier_id = :supplier_id
             ORDER BY o.created_at DESC, o.id DESC"
        );

        $stmt->execute([
            ':supplier_id' => $supplierId
        ]);

        return $stmt->fetchAll();
    }

    // ==========================================
    // GET ORDERS BY STATUS
    // ==========================================

    public function getOrdersByStatus(
        string $status
    ): array {
        $stmt = $this->db->prepare(
            "SELECT
                o.*,
                s.name AS supplier_name
             FROM orders o
             LEFT JOIN suppliers s
                ON s.id = o.supplier_id
             WHERE o.status = :status
             ORDER BY o.created_at DESC, o.id DESC"
        );

        $stmt->execute([
            ':status' => $status
        ]);

        return $stmt->fetchAll();
    }

    // ==========================================
    // CREATE RESTOCK ORDER
    // RETURNS NEW ORDER ID
    // ==========================================

    public function createOrder(
        int $userId,
        string $orderNumber,
        string $totalAmount = '0.00',
        ?int $supplierId = null
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO orders (
                user_id,
                supplier_id,
                order_number,
                total_amount,
                status
             ) VALUES (
                :user_id,
                :supplier_id,
                :order_number,
                :total_amount,
                'PENDING'
             )"
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':supplier_id' => $supplierId,
            ':order_number' => $orderNumber,
            ':total_amount' => $totalAmount
        ]);

        return (int) $this->db->lastInsertId();
    }

    // ==========================================
    // UPDATE ORDER SUPPLIER
    // ONLY WHEN PENDING
    // ==========================================

    public function updateOrderSupplier(
        int $id,
        int $supplierId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE orders
             SET supplier_id = :supplier_id
             WHERE id = :id
             AND status = 'PENDING'"
        );

        $stmt->execute([
            ':supplier_id' => $supplierId,
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================
    // UPDATE ORDER STATUS
    // LOW-LEVEL DATABASE OPERATION
    // ==========================================

    public function updateOrderStatus(
        int $id,
        string $status
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE orders
             SET status = :status
             WHERE id = :id"
        );

        $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================
    // MARK ORDER AS ORDERED
    // PENDING -> ORDERED
    // ==========================================

    public function markAsOrdered(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE orders
             SET status = 'ORDERED',
                 ordered_at = NOW()
             WHERE id = :id
             AND status = 'PENDING'
             AND supplier_id IS NOT NULL"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================
    // MARK ORDER AS RECEIVED
    // ORDERED -> RECEIVED
    // MUST BE CALLED INSIDE A STOCK
    // RECEIVING DATABASE TRANSACTION
    // ==========================================

    public function markAsReceived(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE orders
             SET status = 'RECEIVED',
                 received_at = NOW()
             WHERE id = :id
             AND status = 'ORDERED'"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================
    // CANCEL RESTOCK ORDER
    // ==========================================

    public function cancelOrder(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE orders
             SET status = 'CANCELLED'
             WHERE id = :id
             AND status IN ('PENDING', 'ORDERED')"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================
    // UPDATE ORDER TOTAL
    // ONLY WHEN PENDING
    // ==========================================

    public function updateOrderTotal(
        int $id,
        string $totalAmount
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE orders
             SET total_amount = :total_amount
             WHERE id = :id
             AND status = 'PENDING'"
        );

        $stmt->execute([
            ':total_amount' => $totalAmount,
            ':id' => $id
        ]);

        // Preserve bool return type.
        // execute() can succeed even if no row changed.
        return true;
    }

    // ==========================================
    // DELETE PENDING ORDER
    // ==========================================

    public function deleteOrderById(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM orders
             WHERE id = :id
             AND status = 'PENDING'"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================
    // COUNT ORDERS BY STATUS
    // ==========================================

    public function countOrdersByStatus(
        string $status
    ): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM orders
             WHERE status = :status"
        );

        $stmt->execute([
            ':status' => $status
        ]);

        return (int) $stmt->fetchColumn();
    }
}

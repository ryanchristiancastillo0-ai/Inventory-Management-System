<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class OrderItemRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // GET ALL ORDER ITEMS
    // ==========================================

    public function getAllOrderItems(): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                oi.*,
                p.name AS product_name,
                p.sku AS product_sku
             FROM order_items oi
             INNER JOIN products p
                ON p.id = oi.product_id
             ORDER BY oi.id DESC"
        );

        $stmt->execute();

        return $stmt->fetchAll();
    }

    // ==========================================
    // FIND ORDER ITEM BY ID
    // ==========================================

    public function getOrderItemById(
        int $id
    ): array|false {
        $stmt = $this->db->prepare(
            "SELECT
                oi.*,
                p.name AS product_name,
                p.sku AS product_sku
             FROM order_items oi
             INNER JOIN products p
                ON p.id = oi.product_id
             WHERE oi.id = :id
             LIMIT 1"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch();
    }

    // ==========================================
    // GET ITEMS BY ORDER ID
    // ==========================================

    public function getItemsByOrderId(
        int $orderId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT
                oi.*,
                p.name AS product_name,
                p.sku AS product_sku,
                p.quantity AS current_stock,
                p.price AS selling_price
             FROM order_items oi
             INNER JOIN products p
                ON p.id = oi.product_id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id ASC"
        );

        $stmt->execute([
            ':order_id' => $orderId
        ]);

        return $stmt->fetchAll();
    }

    // ==========================================
    // FIND ITEM BY ORDER AND PRODUCT
    // ==========================================

    public function findItemByOrderAndProduct(
        int $orderId,
        int $productId
    ): array|false {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM order_items
             WHERE order_id = :order_id
             AND product_id = :product_id
             LIMIT 1"
        );

        $stmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $productId
        ]);

        return $stmt->fetch();
    }

    // ==========================================
    // CREATE RESTOCK ORDER ITEM
    // ==========================================

    public function createOrderItem(
        int $orderId,
        int $productId,
        int $quantity,
        string $unitPrice
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO order_items (
                order_id,
                product_id,
                quantity,
                unit_price
             ) VALUES (
                :order_id,
                :product_id,
                :quantity,
                :unit_price
             )"
        );

        return $stmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $productId,
            ':quantity' => $quantity,
            ':unit_price' => $unitPrice
        ]);
    }

    // ==========================================
    // UPDATE ORDER ITEM
    // ==========================================

    public function updateOrderItemById(
        int $id,
        int $quantity,
        string $unitPrice
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE order_items
             SET quantity = :quantity,
                 unit_price = :unit_price
             WHERE id = :id
             AND EXISTS (
                SELECT 1
                FROM orders o
                WHERE o.id = order_items.order_id
                AND o.status = 'PENDING'
             )"
        );

        return $stmt->execute([
            ':quantity' => $quantity,
            ':unit_price' => $unitPrice,
            ':id' => $id
        ]);
    }

    // ==========================================
    // GET ORDER TOTAL
    // ==========================================

    public function getOrderTotal(
        int $orderId
    ): string {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(
                SUM(subtotal), 0
             ) AS total
             FROM order_items
             WHERE order_id = :order_id"
        );

        $stmt->execute([
            ':order_id' => $orderId
        ]);

        $result = $stmt->fetch();

        return (string) ($result['total'] ?? '0.00');
    }

    // ==========================================
    // COUNT ITEMS IN ORDER
    // ==========================================

    public function countItemsByOrderId(
        int $orderId
    ): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM order_items
             WHERE order_id = :order_id"
        );

        $stmt->execute([
            ':order_id' => $orderId
        ]);

        return (int) $stmt->fetchColumn();
    }

    // ==========================================
    // TOTAL QUANTITY BEING RESTOCKED
    // ==========================================

    public function getTotalOrderedQuantity(
        int $orderId
    ): int {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(
                SUM(quantity), 0
             )
             FROM order_items
             WHERE order_id = :order_id"
        );

        $stmt->execute([
            ':order_id' => $orderId
        ]);

        return (int) $stmt->fetchColumn();
    }

    // ==========================================
    // DELETE ORDER ITEM
    // ONLY WHEN ORDER IS PENDING
    // ==========================================

    public function deleteOrderItemById(
        int $id
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM order_items
             WHERE id = :id
             AND EXISTS (
                SELECT 1
                FROM orders o
                WHERE o.id = order_items.order_id
                AND o.status = 'PENDING'
             )"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================
    // DELETE ALL ITEMS FROM PENDING ORDER
    // ==========================================

    public function deleteItemsByOrderId(
        int $orderId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM order_items
             WHERE order_id = :order_id
             AND EXISTS (
                SELECT 1
                FROM orders o
                WHERE o.id = order_items.order_id
                AND o.status = 'PENDING'
             )"
        );

        return $stmt->execute([
            ':order_id' => $orderId
        ]);
    }
}

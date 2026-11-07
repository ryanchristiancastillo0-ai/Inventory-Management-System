<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class StockMovementRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // GET ALL STOCK MOVEMENTS
    public function getAllStockMovements(): array
    {
        // LEFT JOIN products so movement rows carry the display name
        // and image; movement rows for deleted products still appear.
        // Falls back to plain movement rows if the join is unavailable
        // (e.g. the product-photos migration has not been run yet).
        try {
            $stmt = $this->db->prepare(
                "SELECT sm.*, p.name AS product_name, p.sku AS sku,
                        p.image_path AS product_image, c.name AS category_name,
                        p.quantity AS product_quantity, p.low_stock_threshold AS product_threshold
                 FROM stock_movements sm
                 LEFT JOIN products p ON p.id = sm.product_id
                 LEFT JOIN categories c ON c.id = p.category_id
                 ORDER BY sm.created_at DESC, sm.id DESC"
            );

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $error) {
            // Backslash import on purpose: inside a namespace, an unqualified
            // "Throwable" resolves to App\Repositories\Throwable and NEVER
            // matches — the join then fails instead of degrading.
            error_log('[StockMovementRepo] product join unavailable: ' . $error->getMessage());
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM stock_movements
             ORDER BY created_at DESC, id DESC"
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // FIND STOCK MOVEMENT BY ID
    public function getStockMovementById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM stock_movements
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // GET MOVEMENTS BY PRODUCT ID
    public function getMovementsByProductId(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM stock_movements
             WHERE product_id = :product_id
             ORDER BY created_at DESC, id DESC"
        );

        $stmt->execute([
            ':product_id' => $productId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // GET MOVEMENTS BY TYPE
    public function getMovementsByType(string $movementType): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM stock_movements
             WHERE movement_type = :movement_type
             ORDER BY created_at DESC, id DESC"
        );

        $stmt->execute([
            ':movement_type' => $movementType
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // CREATE STOCK MOVEMENT
    public function createStockMovement(
        int $productId,
        string $movementType,
        int $quantity,
        string $remarks
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO stock_movements
             (product_id, movement_type, quantity, remarks)
             VALUES
             (:product_id, :movement_type, :quantity, :remarks)"
        );

        return $stmt->execute([
            ':product_id' => $productId,
            ':movement_type' => $movementType,
            ':quantity' => $quantity,
            ':remarks' => $remarks
        ]);
    }

    // UPDATE STOCK MOVEMENT BY ID
    public function updateStockMovementById(
        int $id,
        int $productId,
        string $movementType,
        int $quantity,
        string $remarks
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE stock_movements SET
                product_id = :product_id,
                movement_type = :movement_type,
                quantity = :quantity,
                remarks = :remarks
             WHERE id = :id"
        );

        return $stmt->execute([
            ':id' => $id,
            ':product_id' => $productId,
            ':movement_type' => $movementType,
            ':quantity' => $quantity,
            ':remarks' => $remarks
        ]);
    }

    // DELETE STOCK MOVEMENT BY ID
    public function deleteStockMovementById(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM stock_movements WHERE id = :id"
        );

        return $stmt->execute([
            ':id' => $id
        ]);
    }


    
    // RECORD MOVEMENT AND UPDATE PRODUCT STOCK
    public function recordStockMovement(
        int $productId,
        string $movementType,
        int $quantity,
        string $remarks
    ): bool {
        try {
            $this->db->beginTransaction();

            // UPDATE PRODUCT QUANTITY
            if ($movementType === 'IN') {
                $stmt = $this->db->prepare(
                    "UPDATE products
                     SET quantity = quantity + :quantity
                     WHERE id = :product_id"
                );
            } else {
                $stmt = $this->db->prepare(
                    "UPDATE products
                     SET quantity = quantity - :quantity
                     WHERE id = :product_id
                     AND quantity >= :minimum_quantity"
                );
            }

            $params = [
                ':quantity' => $quantity,
                ':product_id' => $productId
            ];

            if ($movementType === 'OUT') {
                $params[':minimum_quantity'] = $quantity;
            }

            $stmt->execute($params);

            // PRODUCT NOT FOUND OR INSUFFICIENT STOCK
            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            // SAVE STOCK MOVEMENT HISTORY
            $stmt = $this->db->prepare(
                "INSERT INTO stock_movements
                 (product_id, movement_type, quantity, remarks)
                 VALUES
                 (:product_id, :movement_type, :quantity, :remarks)"
            );

            $saved = $stmt->execute([
                ':product_id' => $productId,
                ':movement_type' => $movementType,
                ':quantity' => $quantity,
                ':remarks' => $remarks
            ]);

            if (!$saved) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();

            return true;

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

}

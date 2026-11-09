<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class ProductRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // GET ALL PRODUCTS (BY USER)
    // ==========================================

    public function getAllProducts(?int $userId = null): array
    {
        if ($userId !== null && $userId > 0) {
            $stmt = $this->db->prepare(
                "SELECT * FROM products WHERE user_id = :user_id ORDER BY id DESC"
            );
            $stmt->execute([
                ":user_id" => $userId
            ]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT * FROM products ORDER BY id DESC"
            );
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // FIND PRODUCT BY ID
    // ==========================================

    public function getProductById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM products WHERE id = :id"
        );

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // FIND PRODUCT BY SKU
    // ==========================================

    public function findProductBySku(string $sku): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM products WHERE sku = :sku"
        );

        $stmt->execute([
            ":sku" => $sku
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // CREATE PRODUCT
    // ==========================================

    public function createProduct(
        int $userId,
        int $categoryId,
        string $sku,
        string $name,
        string $description,
        string $price,
        int $quantity,
        int $lowStockThreshold,
        ?string $imagePath = null
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO products
             (user_id, category_id, sku, name, description, image_path, price,
              quantity, low_stock_threshold)
             VALUES
             (:user_id, :category_id, :sku, :name, :description, :image_path, :price,
              :quantity, :low_stock_threshold)"
        );

        return $stmt->execute([
            ":user_id" => $userId,
            ":category_id" => $categoryId,
            ":sku" => $sku,
            ":name" => $name,
            ":description" => $description,
            ":image_path" => $imagePath,
            ":price" => $price,
            ":quantity" => $quantity,
            ":low_stock_threshold" => $lowStockThreshold
        ]);
    }

    // ==========================================
    // UPDATE PRODUCT
    // ==========================================

    public function updateProductById(
        int $id,
        int $categoryId,
        string $sku,
        string $name,
        string $description,
        string $price,
        int $quantity,
        int $lowStockThreshold
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE products SET
                category_id = :category_id,
                sku = :sku,
                name = :name,
                description = :description,
                price = :price,
                quantity = :quantity,
                low_stock_threshold = :low_stock_threshold,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $id,
            ":category_id" => $categoryId,
            ":sku" => $sku,
            ":name" => $name,
            ":description" => $description,
            ":price" => $price,
            ":quantity" => $quantity,
            ":low_stock_threshold" => $lowStockThreshold
        ]);
    }

    // ==========================================
    // UPDATE PRODUCT IMAGE PATH
    // (separate so the main update never wipes a photo)
    // ==========================================

    public function updateProductImage(int $id, ?string $imagePath): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE products SET
                image_path = :image_path,
                updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $id,
            ":image_path" => $imagePath
        ]);
    }

    // ==========================================
    // DELETE PRODUCT
    // ==========================================

    public function deleteProductById(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM products WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $id
        ]);
    }

    // ==========================================
    // GET LOW STOCK PRODUCTS
    // ==========================================

    public function getLowStockProducts(?int $userId = null): array
    {
        if ($userId !== null && $userId > 0) {
            $stmt = $this->db->prepare(
                "SELECT * FROM products
                 WHERE quantity <= low_stock_threshold
                 AND user_id = :user_id
                 ORDER BY quantity ASC"
            );
            $stmt->execute([
                ":user_id" => $userId
            ]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT * FROM products
                 WHERE quantity <= low_stock_threshold
                 ORDER BY quantity ASC"
            );
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // COUNT ALL PRODUCTS
    // ==========================================

    public function countProducts(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM products"
        );

        return (int) $stmt->fetchColumn();
    }
}

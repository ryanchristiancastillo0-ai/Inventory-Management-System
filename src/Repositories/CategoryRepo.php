<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class CategoryRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // GET ALL CATEGORIES
    // ==========================================

    public function getAllCategories(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM categories ORDER BY name ASC"
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // FIND CATEGORY BY ID
    // ==========================================

    public function getCategoryById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM categories WHERE id = :id"
        );

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // FIND CATEGORY BY NAME
    // ==========================================

    public function findCategoryByName(string $name): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM categories WHERE name = :name"
        );

        $stmt->execute([
            ":name" => $name
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // CREATE CATEGORY
    // ==========================================

    public function createCategory(string $name): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO categories (name)
             VALUES (:name)"
        );

        return $stmt->execute([
            ":name" => $name
        ]);
    }

    // ==========================================
    // UPDATE CATEGORY BY ID
    // ==========================================

    public function updateCategoryById(
        int $id,
        string $name
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE categories
             SET name = :name
             WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $id,
            ":name" => $name
        ]);
    }

    // ==========================================
    // DELETE CATEGORY BY ID
    // ==========================================

    public function deleteCategoryById(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM categories WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $id
        ]);
    }

    // ==========================================
    // COUNT ALL CATEGORIES
    // ==========================================

    public function countCategories(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM categories"
        );

        return (int) $stmt->fetchColumn();
    }
}

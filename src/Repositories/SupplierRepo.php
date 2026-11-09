<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class SupplierRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // GET ALL SUPPLIERS
    // ==========================================

    public function getAllSuppliers(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM suppliers ORDER BY name ASC, id ASC"
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // FIND SUPPLIER BY ID
    // ==========================================

    public function getSupplierById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM suppliers WHERE id = :id LIMIT 1"
        );

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // FIND SUPPLIER BY NAME
    // (unique guard — utf8mb4_unicode_ci makes
    //  the comparison case-insensitive)
    // ==========================================

    public function findSupplierByName(string $name): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM suppliers WHERE name = :name LIMIT 1"
        );

        $stmt->execute([
            ":name" => $name
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // COUNT ORDERS LINKED TO A SUPPLIER
    // (delete guard — fk_orders_supplier)
    // ==========================================

    public function countOrdersForSupplier(int $supplierId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM orders WHERE supplier_id = :supplier_id"
        );

        $stmt->execute([
            ":supplier_id" => $supplierId
        ]);

        return (int) $stmt->fetchColumn();
    }

    // ==========================================
    // CREATE SUPPLIER
    // RETURNS NEW SUPPLIER ID
    // ==========================================

    public function createSupplier(
        string $name,
        ?string $contactPerson,
        ?string $email,
        ?string $phone,
        ?string $address
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO suppliers (
                name,
                contact_person,
                email,
                phone,
                address
             ) VALUES (
                :name,
                :contact_person,
                :email,
                :phone,
                :address
             )"
        );

        $stmt->execute([
            ":name"           => $name,
            ":contact_person" => $contactPerson,
            ":email"          => $email,
            ":phone"          => $phone,
            ":address"        => $address
        ]);

        return (int) $this->db->lastInsertId();
    }

    // ==========================================
    // UPDATE SUPPLIER BY ID
    // ==========================================

    public function updateSupplierById(
        int $id,
        string $name,
        ?string $contactPerson,
        ?string $email,
        ?string $phone,
        ?string $address
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE suppliers
             SET name           = :name,
                 contact_person = :contact_person,
                 email          = :email,
                 phone          = :phone,
                 address        = :address
             WHERE id = :id"
        );

        $stmt->execute([
            ":name"           => $name,
            ":contact_person" => $contactPerson,
            ":email"          => $email,
            ":phone"          => $phone,
            ":address"        => $address,
            ":id"             => $id
        ]);

        return $stmt->rowCount() > 0;
    }

    // ==========================================
    // DELETE SUPPLIER BY ID
    // A supplier referenced by orders cannot be
    // removed due to fk_orders_supplier — callers
    // should check countOrdersForSupplier() first
    // ==========================================

    public function deleteSupplierById(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM suppliers WHERE id = :id"
        );

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->rowCount() > 0;
    }
}

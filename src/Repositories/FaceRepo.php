<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class FaceRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // SAVE FACE
    // ==========================================

    public function saveFace(
        int $userId,
        string $descriptor
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO user_faces
                (user_id, descriptor)
             VALUES
                (:user_id, :descriptor)"
        );

        return $stmt->execute([
            ":user_id"    => $userId,
            ":descriptor" => $descriptor
        ]);
    }

    // ==========================================
    // FACES OF ONE USER
    // ==========================================

    public function getFacesByUserId(
        int $userId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT
                id,
                user_id,
                descriptor,
                created_at
             FROM user_faces
             WHERE user_id = :user_id
             ORDER BY id"
        );

        $stmt->execute([":user_id" => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // ALL ENROLLED FACES (FOR LOGIN MATCHING)
    // ==========================================

    public function getAllFaces(): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                f.id,
                f.user_id,
                f.descriptor,
                u.username,
                u.role
             FROM user_faces f
             INNER JOIN users u
                 ON u.id = f.user_id
             ORDER BY f.id"
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // COUNTS AND META
    // ==========================================

    public function countFacesByUserId(
        int $userId
    ): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM user_faces
             WHERE user_id = :user_id"
        );

        $stmt->execute([":user_id" => $userId]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function hasAnyEnrollment(): bool
    {
        return (int) ($this->db
            ->query("SELECT 1 FROM user_faces LIMIT 1")
            ->fetchColumn() ?: 0) === 1;
    }

    // ==========================================
    // DELETE
    // ==========================================

    public function deleteFacesByUserId(
        int $userId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM user_faces
             WHERE user_id = :user_id"
        );

        return $stmt->execute([":user_id" => $userId]);
    }
}
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class ReportRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // GET ALL REPORTS (ADMIN)
    // ==========================================

    public function getAllReports(): array
    {
        $stmt = $this->db->query(
            "SELECT
                r.*,
                u.username,
                u.email
             FROM reports AS r
             LEFT JOIN users AS u
                ON r.user_id = u.id
             ORDER BY r.created_at DESC, r.id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // FIND REPORT BY ID
    // ==========================================

    public function getReportById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT
                r.*,
                u.username,
                u.email
             FROM reports AS r
             LEFT JOIN users AS u
                ON r.user_id = u.id
             WHERE r.id = :id"
        );

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // GET REPORTS BY USER
    // ==========================================

    public function getReportsByUserId(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM reports
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC"
        );

        $stmt->execute([
            ":user_id" => $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // CREATE REPORT
    // ==========================================

    public function createReport(
        int $userId,
        string $title,
        string $description,
        string $category
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO reports
                (user_id, title, description, category)
             VALUES
                (:user_id, :title, :description, :category)"
        );

        return $stmt->execute([
            ":user_id" => $userId,
            ":title" => $title,
            ":description" => $description,
            ":category" => $category
        ]);
    }

    // ==========================================
    // UPDATE REPORT STATUS AND RESPONSE
    // ==========================================

    public function updateReport(
        int $id,
        string $status,
        ?string $adminResponse
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE reports SET
                status = :status,
                admin_response = :admin_response
             WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $id,
            ":status" => $status,
            ":admin_response" => $adminResponse
        ]);
    }

    // ==========================================
    // COUNT ALL REPORTS
    // ==========================================

    public function countReports(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM reports"
        );

        return (int) $stmt->fetchColumn();
    }

    // ==========================================
    // COUNT REPORTS BY STATUS
    // ==========================================

    public function countReportsByStatus(string $status): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM reports
             WHERE status = :status"
        );

        $stmt->execute([
            ":status" => $status
        ]);

        return (int) $stmt->fetchColumn();
    }
}

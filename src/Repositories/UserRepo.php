<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config\Database;
use PDO;

class UserRepo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ==========================================
    // FIND USER BY EMAIL
    // ==========================================

    public function findUserByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = :email"
        );

        $stmt->execute([
            ":email" => $email
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // GET USER BY ID
    // ==========================================

    public function getUserById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE id = :id"
        );

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // CREATE USER - EXISTING METHOD
    // ==========================================

    public function createUser(
        string $username,
        string $email,
        string $password_hash
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO users
             (username, email, password_hash)
             VALUES
             (:username, :email, :password_hash)"
        );

        return $stmt->execute([
            ":username" => $username,
            ":email" => $email,
            ":password_hash" => $password_hash
        ]);
    }

    // ==========================================
    // SET ACCOUNT ACTIVE FLAG
    // ==========================================

    public function setUserActive(int $id, bool $active): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET
                is_active = :is_active
             WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $id,
            ":is_active" => $active ? 1 : 0
        ]);
    }

    // ==========================================
    // DELETE USER BY ID
    // ==========================================

    public function deleteUserById(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM users WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $id
        ]);
    }

    // ==========================================
    // UPDATE USER - EXISTING METHOD
    // ==========================================

    public function updateUserById(
        int $id,
        string $username,
        string $password_hash,
        string $email
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE users SET
                username = :username,
                email = :email,
                password_hash = :password_hash
             WHERE id = :id"
        );

        return $stmt->execute([
            ":id" => $id,
            ":username" => $username,
            ":password_hash" => $password_hash,
            ":email" => $email
        ]);
    }

    // ==========================================
    // COUNT ALL USERS
    // ==========================================

    public function countUsers(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM users"
        );

        return (int) $stmt->fetchColumn();
    }

    // ==========================================
    // COUNT USERS BY ROLE
    // ==========================================

    public function countUsersByRole(string $role): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM users WHERE role = :role"
        );

        $stmt->execute([
            ":role" => $role
        ]);

        return (int) $stmt->fetchColumn();
    }

    // ==========================================
    // COUNT USERS THIS MONTH
    // ==========================================

    public function countUsersThisMonth(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*)
             FROM users
             WHERE created_at >= DATE_FORMAT(
                 CURDATE(), '%Y-%m-01'
             )
             AND created_at < DATE_FORMAT(
                 CURDATE() + INTERVAL 1 MONTH,
                 '%Y-%m-01'
             )"
        );

        return (int) $stmt->fetchColumn();
    }

    // ==========================================
    // MONTHLY REGISTRATIONS
    // ==========================================

    public function getMonthlyRegistrations(int $year): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                MONTH(created_at) AS month,
                COUNT(*) AS total
             FROM users
             WHERE created_at >= :start_date
               AND created_at < :end_date
             GROUP BY MONTH(created_at)
             ORDER BY month ASC"
        );

        $stmt->execute([
            ":start_date" => $year . "-01-01",
            ":end_date" => ($year + 1) . "-01-01"
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // RECENT USERS
    // ==========================================

    public function getRecentUsers(int $limit = 5): array
    {
        $limit = max(1, min($limit, 100));

        $stmt = $this->db->prepare(
            "SELECT
                id,
                username,
                email,
                role,
                is_active,
                created_at
             FROM users
             ORDER BY created_at DESC, id DESC
             LIMIT :limit"
        );

        $stmt->bindValue(
            ":limit",
            $limit,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // GET ALL USERS
    // ==========================================

    public function getAllUsers(): array
    {
        $stmt = $this->db->query(
            "SELECT
                id,
                username,
                email,
                role,
                is_active,
                created_at
             FROM users
             ORDER BY created_at DESC, id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // FILTER USERS BY SEARCH AND/OR ROLE
    // ==========================================

    public function getFilteredUsers(string $search = '', string $role = ''): array
    {
        $sql = "SELECT
                    id,
                    username,
                    email,
                    role,
                    is_active,
                    created_at
                 FROM users";

        $params = [];

        // Build WHERE clause based on filters
        $conditions = [];

        // Role filter
        if (!empty($role) && in_array($role, ['ADMIN', 'USER'], true)) {
            $conditions[] = "role = :role";
            $params[':role'] = $role;
        }

        // Search filter (username or email or role)
        if (!empty($search)) {
            $searchTerm = trim($search);
            $conditions[] = "(username LIKE :search OR email LIKE :search OR role LIKE :search)";
            $params[':search'] = "%$searchTerm%";
        }

        // Combine conditions with WHERE
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // Order by created_at DESC, id DESC
        $sql .= " ORDER BY created_at DESC, id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // NEW: CREATE USER WITH ROLE
    // ==========================================

    public function createManagedUser(
        string $username,
        string $email,
        string $passwordHash,
        string $role
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO users
                (username, email, password_hash, role)
             VALUES
                (:username, :email, :password_hash, :role)"
        );

        return $stmt->execute([
            ":username" => $username,
            ":email" => $email,
            ":password_hash" => $passwordHash,
            ":role" => $role
        ]);
    }

    // ==========================================
    // NEW: UPDATE MANAGED USER
    // ==========================================

    public function updateManagedUser(
        int $id,
        string $username,
        string $email,
        string $role,
        ?string $passwordHash = null
    ): bool {
        $sql = "UPDATE users SET
                    username = :username,
                    email = :email,
                    role = :role";

        $params = [
            ":id" => $id,
            ":username" => $username,
            ":email" => $email,
            ":role" => $role
        ];

        // Only update password when a new one is supplied
        if ($passwordHash !== null) {
            $sql .= ", password_hash = :password_hash";
            $params[":password_hash"] = $passwordHash;
        }

        $sql .= " WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    // Self-service profile access excludes authentication secrets.
    public function getProfileById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, phone, phone_verified_at, role, created_at FROM users WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findUserByUsername(string $username): array|false
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile(int $id, string $username, string $email): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET username = :username, email = :email WHERE id = :id'
        );
        return $stmt->execute([':id' => $id, ':username' => $username, ':email' => $email]);
    }

    public function getProfileSecuritySummary(int $id): array|false
    {
        // Return capability flags/counts, never credential material.
        $stmt = $this->db->prepare(
            "SELECT (u.password_hash IS NOT NULL AND u.password_hash <> '') AS has_password,
                (SELECT COUNT(*) FROM user_passkeys p WHERE p.user_id = u.id) AS passkey_count,
                (SELECT COUNT(*) FROM user_faces f WHERE f.user_id = u.id) AS face_count
             FROM users u WHERE u.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($summary) {
            $phoneStmt = $this->db->prepare(
                'SELECT phone, phone_verified_at FROM users WHERE id = :id'
            );
            $phoneStmt->execute([':id' => $id]);
            $phoneRow = $phoneStmt->fetch(PDO::FETCH_ASSOC);

            $summary['phone'] = $phoneRow['phone'] ?? null;
            $summary['phone_verified'] = !empty($phoneRow['phone_verified_at']);
            $summary['phone_verified_at'] = $phoneRow['phone_verified_at'] ?? null;
        }

        return $summary;
    }
}

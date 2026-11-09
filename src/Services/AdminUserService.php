<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepo;
use InvalidArgumentException;
use RuntimeException;

class AdminUserService
{
    private UserRepo $userRepo;

    public function __construct(UserRepo $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    // ==========================================
    // GET ALL USERS
    // ==========================================

    public function getAllUsers(?string $search = null, ?string $role = null): array
    {
        return $this->userRepo->getFilteredUsers($search, $role);
    }

    // ==========================================
    // GET USER BY ID
    // ==========================================

    public function findUserById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $user = $this->userRepo->getUserById($id);

        if (!$user) {
            return null;
        }

        unset($user["password_hash"]);

        return $user;
    }

    // ==========================================
    // GET USER STATISTICS
    // ==========================================

    public function getUserStats(): array
    {
        return [
            "totalUsers" => $this->userRepo->countUsers(),
            "totalAdmins" => $this->userRepo
                ->countUsersByRole("ADMIN"),
            "totalRegularUsers" => $this->userRepo
                ->countUsersByRole("USER")
        ];
    }

    // ==========================================
    // VALIDATE ROLE
    // ==========================================

    private function validateRole(string $role): void
    {
        if (!in_array($role, ["ADMIN", "USER"], true)) {
            throw new InvalidArgumentException(
                "Invalid user role."
            );
        }
    }

    // ==========================================
    // VALIDATE USER DETAILS
    // ==========================================

    private function validateUser(
        string $username,
        string $email
    ): void {
        if ($username === "" || $email === "") {
            throw new InvalidArgumentException(
                "Username and email are required."
            );
        }

        if (strlen($username) > 100) {
            throw new InvalidArgumentException(
                "Username is too long."
            );
        }

        if (strlen($email) > 254) {
            throw new InvalidArgumentException(
                "Email is too long."
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                "Invalid email address."
            );
        }
    }

    // ==========================================
    // CREATE USER
    // ==========================================

    public function createUser(
        string $username,
        string $email,
        string $password,
        string $role = "USER"
    ): bool {
        $username = trim($username);
        $email = strtolower(trim($email));
        $role = strtoupper(trim($role));

        $this->validateUser($username, $email);
        $this->validateRole($role);

        if (strlen($password) < 8) {
            throw new InvalidArgumentException(
                "Password must contain at least 8 characters."
            );
        }

        if ($this->userRepo->findUserByEmail($email)) {
            throw new InvalidArgumentException(
                "Email is already registered."
            );
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        return $this->userRepo->createManagedUser(
            $username,
            $email,
            $passwordHash,
            $role
        );
    }

    // ==========================================
    // UPDATE USER
    //
    // The username and email are identity fields: the admin
    // views them but cannot change them, so they are always
    // taken from the stored record, never from the request.
    // ==========================================

    public function updateUser(
        int $id,
        string $role,
        ?string $password = null,
        ?int $currentAdminId = null
    ): bool {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                "Invalid user ID."
            );
        }

        $role = strtoupper(trim($role));

        $existing = $this->userRepo->getUserById($id);

        if (!$existing) {
            throw new InvalidArgumentException(
                "User not found."
            );
        }

        $this->validateRole($role);

        $username = (string) $existing["username"];
        $email    = (string) $existing["email"];

        // Prevent an administrator from removing
        // their own administrator role.
        if (
            $currentAdminId === $id &&
            $role !== "ADMIN"
        ) {
            throw new InvalidArgumentException(
                "You cannot remove your own ADMIN role."
            );
        }

        // Protect the final administrator.
        if (
            $existing["role"] === "ADMIN" &&
            $role !== "ADMIN" &&
            $this->userRepo->countUsersByRole("ADMIN") <= 1
        ) {
            throw new InvalidArgumentException(
                "You cannot demote the last administrator."
            );
        }

        $passwordHash = null;

        if ($password !== null && $password !== "") {
            if (strlen($password) < 8) {
                throw new InvalidArgumentException(
                    "Password must contain at least 8 characters."
                );
            }

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );
        }

        return $this->userRepo->updateManagedUser(
            $id,
            $username,
            $email,
            $role,
            $passwordHash
        );
    }

    // ==========================================
    // ACTIVATE / DEACTIVATE USER
    // ==========================================

    public function setUserActive(
        int $id,
        bool $active,
        int $currentAdminId
    ): bool {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                "Invalid user ID."
            );
        }

        $user = $this->userRepo->getUserById($id);

        if (!$user) {
            throw new InvalidArgumentException(
                "User not found."
            );
        }

        if ($id === $currentAdminId) {
            throw new InvalidArgumentException(
                "You cannot deactivate your own account."
            );
        }

        // Deactivating an administrator would remove the only
        // non-delete way back in, so admins stay active.
        if ($user["role"] === "ADMIN") {
            throw new InvalidArgumentException(
                "Admin accounts cannot be deactivated."
            );
        }

        return $this->userRepo->setUserActive($id, $active);
    }

    // ==========================================
    // DELETE USER
    // ==========================================

    public function deleteUser(
        int $id,
        int $currentAdminId
    ): never {
        // Deletion is intentionally disabled: the admin can
        // deactivate accounts instead. This is enforced here
        // even though the route is blocked as well.
        throw new RuntimeException(
            "User deletion is disabled. Deactivate the account instead."
        );
    }
}

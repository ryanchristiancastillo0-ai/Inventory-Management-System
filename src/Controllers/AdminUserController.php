<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AdminUserService;
use App\Services\AuthService;
use InvalidArgumentException;
use PDOException;
use RuntimeException;

class AdminUserController
{
    private AdminUserService $adminUserService;
    private AuthService $authService;

    public function __construct(
        AdminUserService $adminUserService,
        AuthService $authService
    ) {
        $this->adminUserService = $adminUserService;
        $this->authService = $authService;
    }

    // ==========================================
    // START SESSION
    // ==========================================

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ==========================================
    // CSRF TOKEN
    // ==========================================

    private function getCsrfToken(): string
    {
        $this->startSession();

        if (!isset($_SESSION["admin_user_csrf"])) {
            $_SESSION["admin_user_csrf"] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION["admin_user_csrf"];
    }

    // ==========================================
    // VALIDATE CSRF TOKEN
    // ==========================================

    private function validateCsrf(): void
    {
        $expected = $this->getCsrfToken();

        $provided = $_POST["_csrf"] ?? "";

        if (
            !is_string($provided) ||
            !hash_equals($expected, $provided)
        ) {
            http_response_code(403);
            exit("Invalid CSRF token.");
        }
    }

// ==========================================
// GET CURRENT ADMIN ID
// ==========================================

private function getCurrentAdminId(): int
{
    $user = $this->authService->getCurrentUser();

    // Check if the user exists
    // if (!is_array($user)) {
    //     http_response_code(401);
    //     exit("Unauthorized: Please log in.");
    // }

    // Check if the user is an ADMIN
    // if (($user["role"] ?? null) !== "ADMIN") {
    //     http_response_code(403);
    //     exit("Access denied: Admin only.");
    // }

    // Validate user ID
    $userId = (int) ($user["id"] ?? 0);

    // if ($userId <= 0) {
    //     http_response_code(401);
    //     exit("Invalid authenticated user.");
    // }

    return $userId;
}

    // ==========================================
    // FLASH MESSAGE
    // ==========================================

    private function flash(
        string $type,
        string $message
    ): void {
        $this->startSession();

        $_SESSION["admin_user_flash"] = [
            "type" => $type,
            "message" => $message
        ];
    }

    // ==========================================
    // REDIRECT
    // ==========================================

    private function redirect(): never
    {
        header("Location: /admin/users", true, 303);
        exit;
    }

    // ==========================================
    // HANDLE ERRORS
    // ==========================================

    private function handleError(\Throwable $error): void
    {
        if ($error instanceof InvalidArgumentException) {
            $this->flash("danger", $error->getMessage());
        } else {
            // Log details on the server, not in the UI.
            error_log((string) $error);

            $this->flash(
                "danger",
                "The operation failed. Please try again."
            );
        }

        $this->redirect();
    }

    // ==========================================
    // SHOW USER MANAGEMENT PAGE
    // ==========================================

    public function index(): void
    {
        $this->getCurrentAdminId();
        $this->startSession();

        // Get filter parameters from URL
        $search = trim((string) ($_GET['search'] ?? ''));
        $role = trim((string) ($_GET['role'] ?? ''));
        $isValidRole = in_array($role, ['ADMIN', 'USER'], true);

        $users = $this->adminUserService->getAllUsers($search, $isValidRole ? $role : '');
        $stats = $this->adminUserService->getUserStats();

        $flash = $_SESSION["admin_user_flash"] ?? null;
        unset($_SESSION["admin_user_flash"]);

        View::render(
            "admin/users/index",
            [
                "title" => "User Management",
                "users" => $users,
                "stats" => $stats,
                "flash" => $flash,
                "csrfToken" => $this->getCsrfToken(),
                "search" => $search,
                "role" => $isValidRole ? $role : '',
                "filterCount" => count($users)
            ],
            "admin"
        );
    }

    // ==========================================
    // CREATE USER
    // ==========================================

    public function store(): void
    {
        $this->getCurrentAdminId();
        $this->validateCsrf();

        try {
            $result = $this->adminUserService->createUser(
                (string) ($_POST["username"] ?? ""),
                (string) ($_POST["email"] ?? ""),
                (string) ($_POST["password"] ?? ""),
                (string) ($_POST["role"] ?? "USER")
            );

            $this->flash(
                $result ? "success" : "danger",
                $result
                    ? "User created successfully."
                    : "Unable to create user."
            );
        } catch (\Throwable $error) {
            $this->handleError($error);
        }

        $this->redirect();
    }

    // ==========================================
    // UPDATE USER
    //
    // Identity fields (username, email) are view-only for
    // the admin, so they are read from the stored record
    // inside the service — never from this request.
    // Only the role (and an optional password reset) can
    // be changed.
    // ==========================================

    public function update(): void
    {
        $adminId = $this->getCurrentAdminId();
        $this->validateCsrf();

        try {
            $result = $this->adminUserService->updateUser(
                (int) ($_POST["id"] ?? 0),
                (string) ($_POST["role"] ?? ""),
                (string) ($_POST["password"] ?? ""),
                $adminId
            );

            $this->flash(
                $result ? "success" : "danger",
                $result
                    ? "User updated successfully."
                    : "Unable to update user."
            );
        } catch (\Throwable $error) {
            $this->handleError($error);
        }

        $this->redirect();
    }

    // ==========================================
    // ACTIVATE / DEACTIVATE USER
    // ==========================================

    public function toggleStatus(): void
    {
        $adminId = $this->getCurrentAdminId();
        $this->validateCsrf();

        $makeActive = (string) ($_POST["is_active"] ?? "") === "1";

        try {
            $result = $this->adminUserService->setUserActive(
                (int) ($_POST["id"] ?? 0),
                $makeActive,
                $adminId
            );

            $this->flash(
                $result ? "success" : "danger",
                $result
                    ? ($makeActive
                        ? "User activated. They can log in again."
                        : "User deactivated. They can no longer log in.")
                    : "Unable to update the account status."
            );
        } catch (\Throwable $error) {
            $this->handleError($error);
        }

        $this->redirect();
    }

    // ==========================================
    // DELETE USER (DISABLED)
    //
    // Deliberately blocked: the admin manages accounts
    // through activate / deactivate instead. Even a forged
    // POST reaches a hard refusal here.
    // ==========================================

    public function destroy(): never
    {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit("Forbidden: user deletion has been disabled. Use activate / deactivate instead.");
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\UserRepo;
use App\Services\SettingsService;
use App\Services\AuthService;
use InvalidArgumentException;
use Throwable;

class SettingsController
{
    private SettingsService $settingsService;
    private AuthService $authService;
    private UserRepo $userRepo;

    public function __construct(
        SettingsService $settingsService,
        AuthService $authService,
        UserRepo $userRepo
    ) {
        $this->settingsService = $settingsService;
        $this->authService = $authService;
        $this->userRepo = $userRepo;
    }

    // ==========================================
    // CURRENT USER
    // ==========================================

private function getCurrentUser(): array
{
    $user = $this->authService->getCurrentUser();

    if (!is_array($user)) {
        if ($this->wantsJson()) {
            $this->sendJson(['success' => false, 'message' => 'Please log in again.'], 401);
        }
        http_response_code(401);
        exit("Please log in.");
    }

    if ((int) ($user["id"] ?? 0) <= 0) {
        if ($this->wantsJson()) {
            $this->sendJson(['success' => false, 'message' => 'Invalid user session.'], 401);
        }
        http_response_code(401);
        exit("Invalid user session.");
    }

    if (!in_array(
        $user["role"] ?? null,
        ["ADMIN", "USER"],
        true
    )) {
        if ($this->wantsJson()) {
            $this->sendJson(['success' => false, 'message' => 'Access denied.'], 403);
        }
        http_response_code(403);
        exit("Access denied.");
    }

    return $user;
}

    // ==========================================
    // SESSION
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

        if (empty($_SESSION["settings_csrf"])) {
            $_SESSION["settings_csrf"] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION["settings_csrf"];
    }

    // ==========================================
    // VERIFY CSRF
    // ==========================================

    private function validateCsrf(): void
    {
        $provided = $_POST["_csrf"] ?? "";

        if (
            !is_string($provided) ||
            !hash_equals(
                $this->getCsrfToken(),
                $provided
            )
        ) {
            if ($this->wantsJson()) {
                $this->sendJson(['success' => false, 'message' => 'Your session expired. Reload the page and try again.'], 403);
            }
            http_response_code(403);
            exit("Invalid CSRF token.");
        }
    }

    // ==========================================
    // FLASH MESSAGE
    // ==========================================

    private function flash(
        string $type,
        string $message
    ): void {
        $this->startSession();

        $_SESSION["settings_flash"] = [
            "type" => $type,
            "message" => $message
        ];
    }

    // ==========================================
    // REDIRECT
    // ==========================================

    private function redirect(): never
    {
        header("Location: /settings", true, 303);
        exit;
    }

    // ==========================================
    // SHOW SETTINGS PAGE
    // ==========================================

public function index(): void
{
    $user = $this->getCurrentUser();
    $userId = (int) $user["id"];

    // Real account record from the database.
    $account = null;

    try {
        $account = $this->settingsService->getAccount($userId);
    } catch (Throwable $error) {
        error_log((string) $error);
    }

    if ($account === null) {
        // Fall back to session data so the page still renders.
        $account = [
            "id" => $userId,
            "username" => (string) ($user["username"] ?? ""),
            "email" => (string) ($user["email"] ?? ""),
            "role" => (string) ($user["role"] ?? "USER"),
            "created_at" => (string) ($user["created_at"] ?? "")
        ];
    }

    // Real preferences (user_settings), with service defaults when missing.
    $settings = [];

    try {
        $settings = $this->settingsService->getSettings($userId);
    } catch (Throwable $error) {
        error_log((string) $error);
    }

    // Flash message set by the update handlers on the previous request.
    $this->startSession();
    $flash = null;

    if (isset($_SESSION["settings_flash"])) {
        $flash = $_SESSION["settings_flash"];
        unset($_SESSION["settings_flash"]);
    }

    // Shell follows the role: admins get the admin console,
    // users get the user workspace. The page content is identical;
    // ROLE decides which sidebar/topbar chrome surrounds it.
    $role = strtoupper(
        (string) ($account["role"] ?? $user["role"] ?? "USER")
    );

    // Mobile-number status for the biometric card (capability flags
    // only, never credential material).
    $security = null;

    try {
        $security = $this->userRepo->getProfileSecuritySummary($userId) ?: null;
    } catch (\Throwable $error) {
        error_log((string) $error);
    }

    View::render(
        "settings/index",
        [
            "title" => "Settings",
            "account" => $account,
            "settings" => $settings,
            "flash" => $flash,
            "security" => $security,
            // Token for the biometric endpoints (face/passkey, X-CSRF-Token)
            // — distinct from $csrfToken (settings_csrf) used by the forms.
            "authCsrfToken" => $this->authService->getCsrfToken(),
            "csrfToken" => $this->getCsrfToken()
        ],
        $role === "ADMIN" ? "admin" : "app"
    );
}

    // ==========================================
    // UPDATE PREFERENCES
    // ==========================================

    private function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private function sendJson(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data);
        exit;
    }

    public function updatePreferences(): void
    {
        $user = $this->getCurrentUser();
        $this->validateCsrf();

        try {
            $theme = $_POST['theme'] ?? 'SYSTEM';
            if (!is_string($theme)) {
                throw new InvalidArgumentException('Invalid interface theme.');
            }
            $theme = strtoupper(trim($theme));
            $emailNotifications = ($_POST['email_notifications'] ?? '') === '1';
            $result = $this->settingsService
                ->updatePreferences(
                    (int) $user["id"],
                    $theme,
                    $emailNotifications
                );

            if ($this->wantsJson()) {
                $this->sendJson([
                    'success' => $result,
                    'message' => $result ? 'Preferences saved.' : 'Unable to save your preferences.',
                    'settings' => $result ? [
                        'theme' => $theme,
                        'email_notifications' => (int) $emailNotifications
                    ] : null
                ], $result ? 200 : 500);
            }

            $this->flash(
                $result ? "success" : "danger",
                $result
                    ? "Preferences updated successfully."
                    : "Unable to update preferences."
            );

        } catch (InvalidArgumentException $error) {
            if ($this->wantsJson()) {
                $this->sendJson(['success' => false, 'message' => $error->getMessage()], 422);
            }
            $this->flash("danger", $error->getMessage());

        } catch (Throwable $error) {
            error_log((string) $error);

            if ($this->wantsJson()) {
                $this->sendJson(['success' => false, 'message' => 'Unable to save your preferences.'], 500);
            }
            $this->flash(
                "danger",
                "Unable to save your preferences."
            );
        }

        $this->redirect();
    }

    // ==========================================
    // UPDATE PROFILE
    // ==========================================

    public function updateProfile(): void
    {
        $user = $this->getCurrentUser();
        $this->validateCsrf();

        try {
            $result = $this->settingsService
                ->updateProfile(
                    (int) $user["id"],
                    (string) ($_POST["username"] ?? ""),
                    (string) ($_POST["email"] ?? "")
                );

            if ($result) {
                $_SESSION['username'] = trim((string) $_POST['username']);
            }

            $this->flash(
                $result ? "success" : "danger",
                $result
                    ? "Profile updated successfully."
                    : "Unable to update profile."
            );

        } catch (InvalidArgumentException $error) {
            $this->flash("danger", $error->getMessage());

        } catch (Throwable $error) {
            error_log((string) $error);

            $this->flash(
                "danger",
                "Unable to update your profile."
            );
        }

        $this->redirect();
    }

    // ==========================================
    // CHANGE PASSWORD
    // ==========================================

    public function changePassword(): void
    {
        $user = $this->getCurrentUser();
        $this->validateCsrf();

        try {
            $result = $this->settingsService
                ->changePassword(
                    (int) $user["id"],
                    (string) ($_POST["current_password"] ?? ""),
                    (string) ($_POST["new_password"] ?? ""),
                    (string) ($_POST["confirm_password"] ?? "")
                );

            $this->flash(
                $result ? "success" : "danger",
                $result
                    ? "Password changed successfully."
                    : "Unable to change password."
            );

        } catch (InvalidArgumentException $error) {
            $this->flash("danger", $error->getMessage());

        } catch (Throwable $error) {
            error_log((string) $error);

            $this->flash(
                "danger",
                "Unable to change your password."
            );
        }

        $this->redirect();
    }
}

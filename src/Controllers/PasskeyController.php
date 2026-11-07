<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\PasskeyService;
use RuntimeException;
use Throwable;

class PasskeyController
{
    private PasskeyService $passkeyService;
    private AuthService $authService;

    public function __construct(
        PasskeyService $passkeyService,
        AuthService $authService
    ) {
        $this->passkeyService = $passkeyService;
        $this->authService = $authService;
    }

    // =========================================
    // JSON RESPONSE
    // =========================================

    private function json(
        array|object $data,
        int $status = 200
    ): void {
        http_response_code($status);

        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');

        echo json_encode(
            $data,
            JSON_THROW_ON_ERROR
        );
    }

    // =========================================
    // READ JSON REQUEST
    // =========================================

    private function requestData(): array
    {
        $body = file_get_contents('php://input');

        if ($body === false || strlen($body) > 262144) {
            throw new RuntimeException(
                'Invalid request size.'
            );
        }

        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new RuntimeException(
                'Invalid JSON request.'
            );
        }

        return $data;
    }

    // =========================================
    // CSRF VALIDATION
    // =========================================

    private function requireCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!$this->authService->verifyCsrfToken($token)) {
            throw new RuntimeException(
                'Invalid security token.'
            );
        }
    }

    // =========================================
    // REQUIRE AUTHENTICATION
    // =========================================

    private function requireUser(): int
    {
        if (!$this->authService->isAuthenticated()) {
            throw new RuntimeException(
                'Please log in first.'
            );
        }

        return (int) $_SESSION['user_id'];
    }

    // =========================================
    // ERROR HANDLER
    // =========================================

    private function handleError(Throwable $e): void
    {
        error_log($e->getMessage());

        $this->json([
            'success' => false,
            'message' => 'Passkey operation failed.'
        ], 400);
    }

    // =========================================
    // REGISTRATION OPTIONS
    // =========================================

    public function registrationOptions(): void
    {
        try {
            $this->requireCsrf();

            $userId = $this->requireUser();

            $options = $this->passkeyService
                ->registrationOptions($userId);

            $this->json([
                'success' => true,
                'options' => $options
            ]);

        } catch (Throwable $e) {
            $this->handleError($e);
        }
    }

    // =========================================
    // VERIFY REGISTRATION
    // =========================================

    public function verifyRegistration(): void
    {
        try {
            $this->requireCsrf();

            $userId = $this->requireUser();

            $data = $this->requestData();

            $success = $this->passkeyService
                ->registerPasskey($userId, $data);

            if (!$success) {
                throw new RuntimeException(
                    'Unable to save passkey.'
                );
            }

            $this->json([
                'success' => true,
                'message' => 'Passkey registered successfully.'
            ]);

        } catch (Throwable $e) {
            $this->handleError($e);
        }
    }

    // =========================================
    // LOGIN OPTIONS
    // =========================================

    public function loginOptions(): void
    {
        try {
            $this->requireCsrf();

            $options = $this->passkeyService
                ->loginOptions();

            $this->json([
                'success' => true,
                'options' => $options
            ]);

        } catch (Throwable $e) {
            $this->handleError($e);
        }
    }

    // =========================================
    // VERIFY LOGIN
    // =========================================

    public function verifyLogin(): void
    {
        try {
            $this->requireCsrf();

            $data = $this->requestData();

            // Verify signature before login session.
            $user = $this->passkeyService
                ->verifyLogin($data);

            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            // Prevent reuse of pre-login CSRF token.
            unset($_SESSION['csrf_token']);

            $this->json([
                'success' => true,
                'message' => 'Login successful.',
                'redirect' => ($user['role'] ?? '') === 'ADMIN'
                    ? '/admin/dashboard'
                    : '/dashboard'
            ]);

        } catch (Throwable $e) {
            $this->handleError($e);
        }
    }
}

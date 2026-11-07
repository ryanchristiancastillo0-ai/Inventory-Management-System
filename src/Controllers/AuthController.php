<?php

namespace App\Controllers;

use App\Core\View;
use App\Services\AuthService;
use App\Services\SmsAuthService;
use InvalidArgumentException;
use PDOException;
use RuntimeException;

class AuthController
{
    private AuthService $authService;
    private SmsAuthService $smsAuthService;

    public function __construct(
        AuthService $authService,
        SmsAuthService $smsAuthService
    ) {
        $this->authService = $authService;
        $this->smsAuthService = $smsAuthService;
    }

    // =============================
    // AUTH PAGE  (login + register on ONE page)
    // =============================

    /**
     * GET /auth?auth=login      -> shows the login panel
     * GET /auth?auth=register   -> shows the register panel
     * GET /auth                 -> defaults to login
     */
    public function showAuth(): void
    {
        if ($this->authService->isAuthenticated()) {
            header("Location: " . (
                ($_SESSION['role'] ?? '') === 'ADMIN'
                    ? '/admin/dashboard'
                    : '/dashboard'
            ));
            exit;
        }

        // Which panel is active? (?auth=login | ?auth=register)
        $tab = strtolower(trim((string) ($_GET['auth'] ?? $_GET['tab'] ?? '')));

        if (!in_array($tab, ['login', 'register'], true)) {
            $tab = 'login';
        }

        // Flash messages (read once, then clear)
        $error   = $_SESSION['error'] ?? null;
        $success = $_SESSION['success'] ?? null;

        unset($_SESSION['error'], $_SESSION['success']);

        // Previously submitted values (so the form can be re-filled)
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);

        View::render("auth/auth", [
            "title"     => $tab === 'register' ? 'Create account' : 'Login',
            "csrfToken" => $this->authService->getCsrfToken(),
            "activeTab" => $tab,
            "error"     => $error,
            "success"   => $success,
            "old"       => is_array($old) ? $old : [],
        ], "guest");
    }

    // =============================
    // OPTIONAL BACKWARD-COMPAT REDIRECTS
    // (only used if something still links to /login or /register)
    // =============================

    public function showLogin(): void
    {
        header("Location: /auth?auth=login");
        exit;
    }

    public function showRegister(): void
    {
        header("Location: /auth?auth=register");
        exit;
    }

    // =============================
    // PASSWORD RECOVERY PAGES (FRONTEND ONLY)
    // =============================

    public function showForgotPassword(): void
    {
        View::render("auth/forgot-password", [
            "title"     => "Forgot password",
            "csrfToken" => $this->authService->getCsrfToken(),
        ], "guest");
    }

    public function showResetPassword(): void
    {
        View::render("auth/reset-password", [
            "title"     => "Reset password",
            "csrfToken" => $this->authService->getCsrfToken(),
        ], "guest");
    }

    // =============================
    // REGISTER USER
    // =============================

    public function register(): void
    {
        $token = $_POST['csrf_token'] ?? '';

        if (!$this->authService->verifyCsrfToken($token)) {
            http_response_code(403);
            exit("Invalid request.");
        }

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Only validated if the field is actually sent
        $passwordConfirmation = $_POST['password_confirmation'] ?? null;

        // Remember what the user typed (re-fills the form on error)
        $_SESSION['old'] = [
            'username' => $username,
            'email'    => $email,
        ];

        if ($passwordConfirmation !== null && $passwordConfirmation !== $password) {
            $_SESSION['error'] = "Passwords do not match.";

            header("Location: /auth?auth=register");
            exit;
        }

        try {
            $success = $this->authService->register(
                $username,
                $email,
                $password
            );

            if ($success) {
                unset($_SESSION['old']);

                $_SESSION['success'] =
                    "Account created! Please sign in.";

                header("Location: /auth?auth=login");
                exit;
            }

            $_SESSION['error'] =
                "Unable to create account.";

        } catch (InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();

        } catch (PDOException $e) {
            error_log($e->getMessage());

            $_SESSION['error'] =
                "Registration failed. Please try again.";
        }

        header("Location: /auth?auth=register");
        exit;
    }

    // =============================
    // LOGIN USER (Email/Password)
    // =============================

    public function login(): void
    {
        $token = $_POST['csrf_token'] ?? '';

        if (!$this->authService->verifyCsrfToken($token)) {
            http_response_code(403);
            exit("Invalid request.");
        }

        if ($this->authService->isAuthenticated()) {
            header("Location: " . (
                ($_SESSION['role'] ?? '') === 'ADMIN'
                    ? '/admin/dashboard'
                    : '/dashboard'
            ));
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $success = $this->authService->login(
            $email,
            $password
        );

        if ($success) {
            unset($_SESSION['old']);

            header("Location: " . (
                ($_SESSION['role'] ?? '') === 'ADMIN'
                    ? '/admin/dashboard'
                    : '/dashboard'
            ));
            exit;
        }

        // Keep the typed email, stay on the login panel
        $_SESSION['old'] = ['email' => $email];

        $_SESSION['error'] =
            "Invalid email or password.";

        header("Location: /auth?auth=login");
        exit;
    }

    // =============================
    // SMS OTP LOGIN
    // =============================

    /**
     * POST /api/auth/sms/request
     * Request an OTP to be sent via SMS
     */
    public function requestSmsOtp(): void
    {
        try {
            $this->requireCsrfJson();
            $data = $this->readJsonBody();

            $phone = trim((string) ($data['phone'] ?? ''));

            if ($phone === '') {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Please enter your mobile number.',
                ], 400);
                return;
            }

            $result = $this->smsAuthService->requestOtp($phone);

            $status = $result['success'] ? 200 : 400;
            $this->jsonResponse($result, $status);
        } catch (PDOException $e) {
            // Database problems must never leak schema details to the client.
            error_log('[SmsAuth] requestOtp DB error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Unable to send the code right now. Please try again later or sign in with your email and password.',
            ], 503);
        } catch (RuntimeException $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/auth/sms/verify
     * Verify the OTP code
     */
    public function verifySmsOtp(): void
    {
        try {
            $this->requireCsrfJson();
            $data = $this->readJsonBody();

            $code = trim((string) ($data['code'] ?? ''));

            if ($code === '') {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Please enter the verification code.',
                ], 400);
                return;
            }

            $result = $this->smsAuthService->verifyOtp($code);

            $status = $result['success'] ? 200 : 400;
            $this->jsonResponse($result, $status);
        } catch (PDOException $e) {
            error_log('[SmsAuth] verifyOtp DB error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Unable to verify the code right now. Please try again shortly.',
            ], 503);
        } catch (RuntimeException $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/auth/sms/resend
     * Resend the OTP code
     */
    public function resendSmsOtp(): void
    {
        try {
            $this->requireCsrfJson();
            // Body may be empty; reading it validates size/JSON first.
            $this->readJsonBody();

            $result = $this->smsAuthService->resendOtp();

            $status = $result['success'] ? 200 : 400;
            $this->jsonResponse($result, $status);
        } catch (PDOException $e) {
            error_log('[SmsAuth] resendOtp DB error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Unable to resend the code right now. Please try again shortly.',
            ], 503);
        } catch (RuntimeException $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // =============================
    // LOGOUT USER
    // =============================

    public function logout(): void
    {
        $token = $_POST['csrf_token'] ?? '';

        if (!$this->authService->verifyCsrfToken($token)) {
            http_response_code(403);
            exit("Invalid request.");
        }

        $this->authService->logout();

        header("Location: /auth?auth=login");
        exit;
    }

    // =============================
    // HELPERS
    // =============================

    /**
     * Read and validate the JSON request body.
     * Follows the same limits as PasskeyController.
     */
    private function readJsonBody(): array
    {
        $body = file_get_contents('php://input');

        if ($body === false || strlen($body) > 262144) {
            throw new RuntimeException('Invalid request size.');
        }

        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON request.');
        }

        return $data;
    }

    /**
     * Validate CSRF token from X-CSRF-Token header.
     */
    private function requireCsrfJson(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!$this->authService->verifyCsrfToken($token)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid security token. Please refresh the page.',
            ], 403);
            exit;
        }
    }

    /**
     * Send JSON response and exit.
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }
}
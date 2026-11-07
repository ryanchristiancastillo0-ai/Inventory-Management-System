<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FaceService;
use App\Services\AuthService;
use RuntimeException;
use Throwable;

class FaceController
{
    private FaceService $faceService;
    private AuthService $authService;

    public function __construct(
        FaceService $faceService,
        AuthService $authService
    ) {
        $this->faceService = $faceService;
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
    // CSRF VALIDATION (HEADER, like PasskeyController)
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
            'message' => $e->getMessage(),
        ], 400);
    }

    // =========================================
    // BEGIN FACE LOGIN (public, pre-login)
    // =========================================

    public function loginOptions(): void
    {
        try {
            $this->requireCsrf();

            $challenge = $this->faceService->beginLogin();

            $this->json([
                'success'   => true,
                'challenge' => $challenge
            ]);

        } catch (Throwable $e) {
            $this->handleError($e);
        }
    }

    // =========================================
    // VERIFY FACE LOGIN AND CREATE SESSION
    // =========================================

    public function verifyLogin(): void
    {
        try {
            $this->requireCsrf();

            $data = $this->requestData();

            // Verify the scan before any session work.
            $result = $this->faceService->verifyLogin($data);

            /* Several accounts share that face: the service stored a
               one-time chooser token; the user must now pick one. */
            if (($result['mode'] ?? 'login') === 'choose') {
                $this->json([
                    'success'       => true,
                    'chooseAccount' => true,
                    'token'         => $result['token'],
                    'accounts'      => $result['accounts']
                ]);
                return;
            }

            $this->createSession($result['user']);

            $this->json([
                'success' => true,
                'message' => 'Login successful.',
                'redirect' => ($result['user']['role'] ?? '') === 'ADMIN'
                    ? '/admin/dashboard'
                    : '/dashboard'
            ]);

        } catch (Throwable $e) {
            $this->handleError($e);
        }
    }

    // =========================================
    // CHOOSE ACCOUNT AFTER MULTI-FACE MATCH
    // =========================================

    public function selectAccount(): void
    {
        try {
            $this->requireCsrf();

            $data = $this->requestData();

            $user = $this->faceService->selectAccount(
                (string) ($data['token'] ?? ''),
                $data['userId'] ?? 0
            );

            $this->createSession($user);

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

    // =========================================
    // SESSION CREATION (SHARED)
    // =========================================

    private function createSession(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Prevent reuse of the pre-login CSRF token.
        unset($_SESSION['csrf_token']);
    }

    // =========================================
    // LIST CURRENT ENROLLMENTS (logged in)
    // =========================================

    public function index(): void
    {
        try {
            $userId = $this->requireUser();

            $this->json([
                'success' => true,
            ] + $this->faceService->getEnrollments($userId));

        } catch (Throwable $e) {
            $this->handleError($e);
        }
    }

    // =========================================
    // ENROLL FACES (logged in)
    // =========================================

    public function enroll(): void
    {
        try {
            $this->requireCsrf();
            $userId = $this->requireUser();

            $data = $this->requestData();

            $count = $this->faceService->enrollFace(
                $userId,
                (array) ($data['descriptors'] ?? [])
            );

            $this->json([
                'success' => true,
                'message' => 'Face enrolled successfully.',
                'count'   => $count
            ]);

        } catch (Throwable $e) {
            $this->handleError($e);
        }
    }

    // =========================================
    // DELETE FACES (logged in)
    // =========================================

    public function delete(): void
    {
        try {
            $this->requireCsrf();
            $userId = $this->requireUser();

            $this->faceService->deleteFaces($userId);

            $this->json([
                'success' => true,
                'message' => 'Face enrollment removed.'
            ]);

        } catch (Throwable $e) {
            $this->handleError($e);
        }
    }
}
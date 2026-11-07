<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // CHECK IF USER IS LOGGED IN
    public function handle(): void
    {
        if (!$this->authService->isAuthenticated()) {
            if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-store');
                echo json_encode(['success' => false, 'message' => 'Please log in again.']);
                exit;
            }
            header("Location: /auth?auth=login");
            exit;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;

class RoleMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // CHECK USER ROLE
    public function handle(string $role): void
    {
        if (!$this->authService->isAuthenticated()) {
            header("Location: /auth?auth=login");
            exit;
        }

        // A deactivated account loses its session immediately.
        if (!$this->authService->isActiveSession()) {
            $this->authService->logout();
            header("Location: /auth?auth=login&status=deactivated");
            exit;
        }

        if (!$this->authService->hasRole($role)) {
            http_response_code(403);
            exit("Access denied");
        }
    }
}

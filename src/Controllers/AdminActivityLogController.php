<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\ActivityLogService;
use App\Services\AuthService;

class AdminActivityLogController
{
    private ActivityLogService $activityLogService;
    private AuthService $authService;

    public function __construct(
        ActivityLogService $activityLogService,
        AuthService $authService
    ) {
        $this->activityLogService = $activityLogService;
        $this->authService = $authService;
    }

    // ==========================================
    // SHOW ACTIVITY LOGS PAGE
    // ==========================================

    public function index(): void
    {
        // Check authenticated administrator
        $user = $this->authService->getCurrentUser();

        // if (
        //     !$user ||
        //     ($user["role"] ?? null) !== "ADMIN"
        // ) {
        //     http_response_code(403);
        //     exit("Access denied.");
        // }

        // Get current page from URL
        $page = filter_input(
            INPUT_GET,
            "page",
            FILTER_VALIDATE_INT
        );

        if ($page === false || $page === null) {
            $page = 1;
        }

        $page = max(1, $page);

        // Get activity logs
        $activityData = $this->activityLogService
            ->getActivityLogs($page);

        // Get activity statistics
        $stats = $this->activityLogService
            ->getActivityStats();

        // Render admin view
        View::render(
            "admin/activity-logs/index",
            [
                "title" => "Activity Logs",
                "logs" => $activityData["logs"],
                "stats" => $stats,
                "currentPage" => $activityData["currentPage"],
                "totalPages" => $activityData["totalPages"],
                "totalLogs" => $activityData["totalLogs"]
            ],
            "admin"
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ActivityLogRepo;
use InvalidArgumentException;

class ActivityLogService
{
    private ActivityLogRepo $activityLogRepo;

    public function __construct(
        ActivityLogRepo $activityLogRepo
    ) {
        $this->activityLogRepo = $activityLogRepo;
    }

    // ==========================================
    // RECORD ACTIVITY
    // ==========================================

    public function recordActivity(
        ?int $userId,
        string $action,
        string $description
    ): bool {
        $action = strtoupper(trim($action));
        $description = trim($description);

        if ($userId !== null && $userId <= 0) {
            throw new InvalidArgumentException(
                "Invalid user ID."
            );
        }

        if ($action === "" || strlen($action) > 100) {
            throw new InvalidArgumentException(
                "Invalid activity action."
            );
        }

        if ($description === "") {
            throw new InvalidArgumentException(
                "Activity description is required."
            );
        }

        return $this->activityLogRepo->createLog(
            $userId,
            $action,
            $description
        );
    }

    // ==========================================
    // GET ACTIVITY LOGS WITH PAGINATION
    // ==========================================

    public function getActivityLogs(
        int $page = 1,
        int $perPage = 20
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));

        $totalLogs = $this->activityLogRepo->countLogs();

        $totalPages = max(
            1,
            (int) ceil($totalLogs / $perPage)
        );

        $page = min($page, $totalPages);

        $offset = ($page - 1) * $perPage;

        $logs = $this->activityLogRepo->getAllLogs(
            $perPage,
            $offset
        );

        return [
            "logs" => $logs,
            "totalLogs" => $totalLogs,
            "currentPage" => $page,
            "totalPages" => $totalPages,
            "perPage" => $perPage
        ];
    }

    // ==========================================
    // FIND ACTIVITY LOG BY ID
    // ==========================================

    public function findActivityLogById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $log = $this->activityLogRepo->getLogById($id);

        return $log ?: null;
    }

    // ==========================================
    // GET LOGS BY USER
    // ==========================================

    public function getLogsByUserId(int $userId, ?int $limit = null): array
    {
        if ($userId <= 0) {
            return [];
        }

        return $this->activityLogRepo
            ->getLogsByUserId($userId, $limit);
    }

    // ==========================================
    // GET LOGS BY ACTION
    // ==========================================

    public function getLogsByAction(string $action): array
    {
        $action = strtoupper(trim($action));

        if ($action === "") {
            return [];
        }

        return $this->activityLogRepo
            ->getLogsByAction($action);
    }

    // ==========================================
    // GET DASHBOARD STATISTICS
    // ==========================================

    public function getActivityStats(): array
    {
        return [
            "totalLogs" => $this->activityLogRepo
                ->countLogs(),

            "todayLogs" => $this->activityLogRepo
                ->countTodayLogs()
        ];
    }

    // ==========================================
    // GET RECENT ACTIVITIES
    // ==========================================

    public function getRecentActivities(
        int $limit = 5
    ): array {
        return $this->activityLogRepo
            ->getRecentLogs($limit);
    }
}

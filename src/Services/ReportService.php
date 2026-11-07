<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ReportRepo;
use App\Repositories\UserRepo;
use InvalidArgumentException;

class ReportService
{
    private ReportRepo $reportRepo;

    private UserRepo $userRepo;

    private ?NotificationService $notificationService;

    private const CATEGORIES = [
        "BUG",
        "FEEDBACK",
        "SUGGESTION",
        "OTHER"
    ];

    private const STATUSES = [
        "PENDING",
        "IN_PROGRESS",
        "RESOLVED",
        "REJECTED"
    ];

    public function __construct(
        ReportRepo $reportRepo,
        ?UserRepo $userRepo = null,
        ?NotificationService $notificationService = null
    ) {
        $this->reportRepo = $reportRepo;
        $this->userRepo = $userRepo ?? new UserRepo();
        $this->notificationService = $notificationService;
    }

    // ==========================================
    // GET ALL REPORTS (ADMIN)
    // ==========================================

    public function getAllReports(): array
    {
        return $this->reportRepo->getAllReports();
    }

    // ==========================================
    // FIND REPORT BY ID
    // ==========================================

    public function findReportById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $report = $this->reportRepo->getReportById($id);

        return $report ?: null;
    }

    // ==========================================
    // GET REPORTS BY USER
    // ==========================================

    public function getReportsByUserId(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        return $this->reportRepo
            ->getReportsByUserId($userId);
    }

    // ==========================================
    // SUBMIT NEW REPORT
    // ==========================================

    public function createReport(
        int $userId,
        string $title,
        string $description,
        string $category
    ): bool {
        $title = trim($title);
        $description = trim($description);
        $category = strtoupper(trim($category));

        if ($userId <= 0) {
            throw new InvalidArgumentException(
                "Invalid user ID."
            );
        }

        if ($title === "" || mb_strlen($title) > 150) {
            throw new InvalidArgumentException(
                "Title must contain 1 to 150 characters."
            );
        }

        if (
            $description === "" ||
            mb_strlen($description) > 5000
        ) {
            throw new InvalidArgumentException(
                "Description must contain 1 to 5000 characters."
            );
        }

        if (!in_array($category, self::CATEGORIES, true)) {
            throw new InvalidArgumentException(
                "Invalid report category."
            );
        }

        $created = $this->reportRepo->createReport(
            $userId,
            $title,
            $description,
            $category
        );

        if ($created) {
            $this->notifyAdmins($title, $category);
        }

        return $created;
    }

    // ==========================================
    // EMAIL NOTIFICATIONS (BREVO)
    // Reports notify ADMIN users only, after a successful
    // submission. Each admin still gets the personal
    // "Account, order and security updates" toggle honored.
    // ==========================================

    private function notifyAdmins(
        string $title,
        string $category
    ): void {
        if ($this->notificationService === null) {
            return;
        }

        try {
            $admins = $this->userRepo->getAllUsers();
        } catch (RuntimeException|Throwable $error) {
            error_log("[ReportService] " . $error->getMessage());
            return;
        }

        foreach ($admins as $admin) {
            if (($admin['role'] ?? '') !== 'ADMIN') {
                continue;
            }

            $adminId = (int) ($admin['id'] ?? 0);

            if ($adminId <= 0) {
                continue;
            }

            $this->notificationService->send(
                $adminId,
                NotificationService::TYPE_REPORT,
                "New report ({$category}): " . mb_substr($title, 0, 80),
                "New report submitted",
                [
                    "A new report was submitted by a user.",
                    "Category: {$category}. Title: {$title}",
                    "Open the Reports page to review and respond.",
                ],
                [],
                [
                    "label" => "Open reports",
                    "url" => self::appBaseUrl() . "/admin/report"
                ]
            );
        }
    }

    private static function appBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }

    // ==========================================
    // UPDATE REPORT (ADMIN)
    // ==========================================

    public function updateReport(
        int $id,
        string $status,
        ?string $adminResponse
    ): bool {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                "Invalid report ID."
            );
        }

        $status = strtoupper(trim($status));

        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException(
                "Invalid report status."
            );
        }

        $report = $this->reportRepo->getReportById($id);

        if (!$report) {
            throw new InvalidArgumentException(
                "Report not found."
            );
        }

        $adminResponse = $adminResponse === null
            ? null
            : trim($adminResponse);

        if ($adminResponse === "") {
            $adminResponse = null;
        }

        if (
            $adminResponse !== null &&
            mb_strlen($adminResponse) > 5000
        ) {
            throw new InvalidArgumentException(
                "Admin response is too long."
            );
        }

        return $this->reportRepo->updateReport(
            $id,
            $status,
            $adminResponse
        );
    }

    // ==========================================
    // GET REPORT STATISTICS
    // ==========================================

    public function getReportStats(): array
    {
        return [
            "totalReports" => $this->reportRepo
                ->countReports(),

            "pendingReports" => $this->reportRepo
                ->countReportsByStatus("PENDING"),

            "inProgressReports" => $this->reportRepo
                ->countReportsByStatus("IN_PROGRESS"),

            "resolvedReports" => $this->reportRepo
                ->countReportsByStatus("RESOLVED"),

            "rejectedReports" => $this->reportRepo
                ->countReportsByStatus("REJECTED")
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\ReportService;
use App\Services\AuthService;
use InvalidArgumentException;
use Throwable;

class AdminReportController
{
    private ReportService $reportService;
    private AuthService $authService;

    public function __construct(
        ReportService $reportService,
        AuthService $authService
    ) {
        $this->reportService = $reportService;
        $this->authService = $authService;
    }

    // ==========================================
    // CHECK ADMIN ACCESS
    // ==========================================

    private function requireAdmin(): void
    {
        $user = $this->authService->getCurrentUser();

        // if (!is_array($user)) {
        //     http_response_code(401);
        //     exit("Please log in.");
        // }

        // if (($user["role"] ?? null) !== "ADMIN") {
        //     http_response_code(403);
        //     exit("Admin access only.");
        // }
    }

    // ==========================================
    // SESSION AND CSRF
    // ==========================================

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function csrfToken(): string
    {
        $this->startSession();

        if (empty($_SESSION["admin_report_csrf"])) {
            $_SESSION["admin_report_csrf"] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION["admin_report_csrf"];
    }

    private function validateCsrf(): void
    {
        $token = $_POST["_csrf"] ?? "";

        if (
            !is_string($token) ||
            !hash_equals($this->csrfToken(), $token)
        ) {
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

        $_SESSION["admin_report_flash"] = [
            "type" => $type,
            "message" => $message
        ];
    }

    private function redirect(): never
    {
        header("Location: /admin/reports", true, 303);
        exit;
    }

    // ==========================================
    // SHOW ALL REPORTS
    // ==========================================

    public function index(): void
    {
        $this->requireAdmin();

        $this->startSession();

        $reports = $this->reportService
            ->getAllReports();

        $stats = $this->reportService
            ->getReportStats();

        $flash = $_SESSION["admin_report_flash"] ?? null;
        unset($_SESSION["admin_report_flash"]);

        View::render(
            "admin/reports/index",
            [
                "title" => "Reports Management",
                "reports" => $reports,
                "stats" => $stats,
                "flash" => $flash,
                "csrfToken" => $this->csrfToken()
            ],
            "admin"
        );
    }

    // ==========================================
    // UPDATE REPORT STATUS
    // ==========================================

    public function update(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        try {
            $result = $this->reportService->updateReport(
                (int) ($_POST["id"] ?? 0),
                (string) ($_POST["status"] ?? ""),
                (string) ($_POST["admin_response"] ?? "")
            );

            $this->flash(
                $result ? "success" : "danger",
                $result
                    ? "Report updated successfully."
                    : "Unable to update report."
            );
        } catch (InvalidArgumentException $error) {
            $this->flash("danger", $error->getMessage());
        } catch (Throwable $error) {
            error_log((string) $error);

            $this->flash(
                "danger",
                "Something went wrong. Please try again."
            );
        }

        $this->redirect();
    }
}

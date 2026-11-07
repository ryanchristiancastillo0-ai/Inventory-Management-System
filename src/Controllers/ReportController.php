<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\ReportService;
use App\Services\AuthService;
use InvalidArgumentException;
use Throwable;

class ReportController
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
    // GET AUTHENTICATED USER ID
    // ==========================================

    private function getUserId(): int
    {
        $user = $this->authService->getCurrentUser();

        if (!is_array($user)) {
            http_response_code(401);
            exit("Please log in.");
        }

        if (($user["role"] ?? null) !== "USER") {
            http_response_code(403);
            exit("User access only.");
        }

        $userId = (int) ($user["id"] ?? 0);

        if ($userId <= 0) {
            http_response_code(401);
            exit("Invalid session.");
        }

        return $userId;
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

        if (empty($_SESSION["report_csrf"])) {
            $_SESSION["report_csrf"] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION["report_csrf"];
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

        $_SESSION["report_flash"] = [
            "type" => $type,
            "message" => $message
        ];
    }

    private function redirect(): never
    {
        header("Location: /reports", true, 303);
        exit;
    }

    // ==========================================
    // SHOW USER REPORTS PAGE
    // ==========================================

    public function index(): void
    {
        $userId = $this->getUserId();

        $this->startSession();

        $reports = $this->reportService
            ->getReportsByUserId($userId);

        $flash = $_SESSION["report_flash"] ?? null;
        unset($_SESSION["report_flash"]);

        View::render(
            "reports/index",
            [
                "title" => "My Reports",
                "reports" => $reports,
                "flash" => $flash,
                "csrfToken" => $this->csrfToken()
            ],
            "app"
        );
    }

    // ==========================================
    // SUBMIT REPORT
    // ==========================================

    public function store(): void
    {
        $userId = $this->getUserId();
        $this->validateCsrf();

        try {
            $result = $this->reportService->createReport(
                $userId,
                (string) ($_POST["title"] ?? ""),
                (string) ($_POST["description"] ?? ""),
                (string) ($_POST["category"] ?? "BUG")
            );

            $this->flash(
                $result ? "success" : "danger",
                $result
                    ? "Report submitted successfully."
                    : "Unable to submit report."
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

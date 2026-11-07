<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\NotificationService;
use PDOException;

/**
 * Notifications feed (delivery log) for the signed-in user.
 */
class NotificationController
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // ==========================================
    // GET /notifications
    // ==========================================

    public function index(): void
    {
        $userId = (int) ($_SESSION["user_id"] ?? 0);

        if ($userId <= 0) {
            jsonResponse(
                ["error" => "Unauthorized"],
                401
            );
            return;
        }

        try {
            $notifications = $this->notificationService->getByUserId($userId);
        } catch (PDOException $error) {
            error_log("[NotificationController] " . $error->getMessage());

            jsonResponse(
                ["error" => "Could not load notifications."],
                500
            );
            return;
        }

        jsonResponse([
            "count" => count($notifications),
            "notifications" => array_map(
                static function (array $row): array {
                    return [
                        "id" => (int) $row["id"],
                        "type" => (string) $row["type"],
                        "subject" => (string) $row["subject"],
                        "recipient_email" => (string) $row["recipient_email"],
                        "status" => (string) $row["status"],
                        "created_at" => (string) $row["created_at"],
                        "sent_at" => (string) ($row["sent_at"] ?? "")
                    ];
                },
                $notifications
            )
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NotificationRepo;
use App\Repositories\SettingsRepo;
use App\Repositories\UserRepo;
use RuntimeException;
use Throwable;

/**
 * Orchestrates in-product email notifications.
 *
 * Every notification is persisted into the `notifications` delivery
 * log, and the email itself is rendered and delivered through Brevo
 * by EmailService.
 *
 * NOTE: all public methods swallow delivery failures (they are
 * recorded and logged instead) so that a broken mail configuration
 * can never break register/login/order/profile flows.
 */
class NotificationService
{
    public const TYPE_ORDER = "order";
    public const TYPE_STOCK_MOVEMENT = "stock_movement";
    public const TYPE_REPORT = "report";
    public const TYPE_PASSWORD_RESET = "password_reset";

    private NotificationRepo $notificationRepo;
    private EmailService $emailService;
    private UserRepo $userRepo;
    private SettingsRepo $settingsRepo;

    public function __construct(
        NotificationRepo $notificationRepo,
        EmailService $emailService,
        UserRepo $userRepo,
        SettingsRepo $settingsRepo
    ) {
        $this->notificationRepo = $notificationRepo;
        $this->emailService = $emailService;
        $this->userRepo = $userRepo;
        $this->settingsRepo = $settingsRepo;
    }

    // ==========================================
    // SEND (PUBLIC API)
    // ==========================================

    /**
     * Sends an email notification to one user.
     *
     * @param string $type One of NotificationService::TYPE_*.
     * @param array<int, string> $paragraphs Body paragraphs.
     * @param array<string, string> $changes "What changed" rows.
     * @param array{label: string, url: string}|null $cta Button.
     *
     * @return string[] Status reported to renderer:
     *         "sent" | "skipped" | "failed".
     */
    public function send(
        int $userId,
        string $type,
        string $subject,
        string $heading,
        array $paragraphs = [],
        array $changes = [],
        ?array $cta = null
    ): string {
        return $this->deliver(
            $userId,
            $type,
            $subject,
            static function () use ($heading, $paragraphs, $changes, $cta): array {
                return [
                    EmailTemplates::render(
                        $heading,
                        $paragraphs,
                        $changes,
                        $cta
                    ),
                    EmailService::buildPlainText(
                        $heading,
                        $paragraphs,
                        $changes
                    )
                ];
            }
        );
    }

    /**
     * Sends a notification with a pre-rendered email body —
     * used by the password-reset flow, whose two emails
     * (reset-code + password-changed) have dedicated templates.
     *
     * @param callable():array{0:string,1:string} $bodyRenderer
     *        Returns [html, text].
     */
    public function sendTemplate(
        int $userId,
        string $type,
        string $subject,
        callable $bodyRenderer
    ): string {
        return $this->deliver(
            $userId,
            $type,
            $subject,
            $bodyRenderer
        );
    }

    // ==========================================
    // DELIVERY PIPELINE (PRIVATE)
    // ==========================================

    /**
     * Shared pipeline for both senders: type guard, recipient
     * lookup, settings toggle, delivery-log row, Brevo send and
     * outcome recording.
     *
     * @param callable():array{0:string,1:string} $bodyRenderer
     *        Returns [html, text].
     */
    private function deliver(
        int $userId,
        string $type,
        string $subject,
        callable $bodyRenderer
    ): string {
        $type = strtolower(trim($type));

        if (
            $userId <= 0 ||
            !in_array($type, [
                self::TYPE_ORDER,
                self::TYPE_STOCK_MOVEMENT,
                self::TYPE_REPORT,
                self::TYPE_PASSWORD_RESET
            ], true)
        ) {
            return "skipped";
        }

        // 1) USER (recipient)
        $user = $this->userRepo->getUserById($userId);

        if (
            $user === false ||
            !isset($user["email"]) ||
            !filter_var($user["email"], FILTER_VALIDATE_EMAIL)
        ) {
            return "skipped";
        }

        // 2) TOGGLE: honor "Account, order and security updates" toggle
        //    kept in user_settings.email_notifications.
        //
        //    Password-reset mail is security-critical: a user who
        //    turned off optional notifications must still be able to
        //    recover their account, so that type always sends.
        if (
            $type !== self::TYPE_PASSWORD_RESET
            && !$this->isEmailNotificationsEnabled($userId)
        ) {
            return "skipped";
        }

        $recipientEmail = (string) $user["email"];
        $recipientName = (string) ($user["username"] ?? "");

        // 3) DELIVERY LOG (pending)
        $notificationId = $this->notificationRepo->create([
            "user_id" => $userId,
            "type" => $type,
            "subject" => $subject,
            "recipient_email" => $recipientEmail,
            "provider" => "brevo"
        ]);

        if ($notificationId <= 0) {
            return "skipped";
        }

        // 4) SEND THROUGH BREVO
        if (!$this->emailService->isConfigured()) {
            $this->notificationRepo->markSkipped(
                $notificationId,
                "Brevo is not configured (BREVO_API_KEY missing)."
            );

            return "skipped";
        }

        try {
            [$html, $text] = $bodyRenderer();

            $messageId = $this->emailService->send(
                $subject,
                $html,
                $text,
                $recipientEmail,
                $recipientName
            );

            $this->notificationRepo->markSent(
                $notificationId,
                $messageId
            );

            return "sent";
        } catch (Throwable $error) {
            $this->notificationRepo->markFailed(
                $notificationId,
                $error->getMessage()
            );

            error_log(
                "[Notification] Failed to send {$type} notification " .
                "to user #{$userId}: " . $error->getMessage()
            );

            return "failed";
        }
    }

    // ==========================================
    // LIST (FEED FOR SETTINGS / DASHBOARD)
    // ==========================================

    public function getByUserId(int $userId, int $limit = 50): array
    {
        return $this->notificationRepo->getByUserId(
            $userId,
            $limit
        );
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function isEmailNotificationsEnabled(int $userId): bool
    {
        try {
            $settings = $this->settingsRepo
                ->getSettingsByUserId($userId);
        } catch (RuntimeException|Throwable) {
            // If settings cannot be read, fail closed: no emails.
            return false;
        }

        if ($settings === false) {
            // No saved settings row yet: default matches
            // SettingsService::getSettings() (enabled).
            return true;
        }

        return (int) ($settings["email_notifications"] ?? 1) === 1;
    }
}

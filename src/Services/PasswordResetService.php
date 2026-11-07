<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuthRepo;
use App\Repositories\PasswordResetRepo;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * The whole "forgot password" flow:
 *
 *  1. requestReset()  — email + 6-digit code, emailed via Brevo
 *     (sendTemplate + its dedicated EmailTemplates::renderResetCode).
 *  2. verifyCode()    — checks the code, issues a one-time token
 *     that the frontend carries to the reset page.
 *  3. completeReset() — token + new password -> password updated,
 *     "your password was changed" confirmation email.
 *
 * Every code is stored hashed (bcrypt) and every token hashed
 * (sha-256), so the table never holds anything usable.
 *
 * Delivery normally respects the "Account, order and security
 * updates" toggle (NotificationService checks it). Password-reset
 * mail is security-critical and is always delivered. Failures from
 * Brevo are swallowed: they are recorded in the notifications
 * delivery log, never break this flow.
 */
class PasswordResetService
{
    private const CODE_TTL_MINUTES = 10;

    private const TOKEN_TTL_MINUTES = 10;

    private PasswordResetRepo $passwordResetRepo;

    private AuthRepo $authRepo;

    private NotificationService $notificationService;

    public function __construct(
        PasswordResetRepo $passwordResetRepo,
        AuthRepo $authRepo,
        NotificationService $notificationService
    ) {
        $this->passwordResetRepo = $passwordResetRepo;
        $this->authRepo = $authRepo;
        $this->notificationService = $notificationService;
    }

    // =========================================
    // STEP 1 — SEND THE RESET CODE
    // =========================================

    /**
     * Request handler used by POST /api/auth/password-reset/request
     *
     * @throws InvalidArgumentException when the email is unregistered,
     *              so the UI can tell the user to use the email linked
     *              to an account (requested behaviour: only registered
     *              emails can request a reset).
     */
    public function requestReset(
        string $email,
        string $appBaseUrl
    ): bool {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Please enter a valid email address.");
        }

        $user = $this->authRepo->findUserByEmail($email);

        if (!$user || is_array($user) === false) {
            throw new InvalidArgumentException(
                "Invalid email — this email is not registered to any account."
            );
        }

        $userId = (int) $user["id"];

        if ($userId <= 0) {
            throw new InvalidArgumentException(
                "Invalid email — this email is not registered to any account."
            );
        }

        // Only one live code per email at a time: kill pending ones.
        $this->passwordResetRepo->invalidatePending($email);

        $code = $this->generateCode();
        $codeHash = password_hash($code, PASSWORD_DEFAULT);

        $this->passwordResetRepo->create(
            $userId,
            $email,
            $codeHash,
            self::CODE_TTL_MINUTES
        );

        // Dedicated password-reset template.
        $this->notificationService->sendTemplate(
            $userId,
            NotificationService::TYPE_PASSWORD_RESET,
            "Your password reset code",
            function () use ($user, $code, $appBaseUrl): array {
                return [
                    EmailTemplates::renderResetCode(
                        (string) ($user["username"] ?? "there"),
                        $code,
                        self::CODE_TTL_MINUTES,
                        $appBaseUrl
                    ),
                    "=== Reset your password ===\n\n"
                    . "Hi " . ($user["username"] ?? "there")
                    . ". Someone requested a password reset for your Inventory Hub account.\n\n"
                    . "Your verification code is: {$code}\n\n"
                    . "The code expires in " . self::CODE_TTL_MINUTES
                    . " minutes and can only be used once.\n\n"
                    . "Didn't request this? You can safely ignore this email - your password stays unchanged.\n\n"
                    . "- Inventory Hub",
                ];
            }
        );

        return true;
    }

    // =========================================
    // STEP 2 — VERIFY THE CODE, ISSUE TOKEN
    // =========================================

    /**
     * Verifies the emailed 6-digit code and returns a one-time
     * reset token for the frontend to hand to the reset page.
     *
     * @throws InvalidArgumentException on any verification failure.
     */
    public function verifyCode(
        string $email,
        string $code
    ): string {
        $email = strtolower(trim($email));
        $code = trim($code);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                "This code is no longer valid. Please request a new one."
            );
        }

        if ($code === "" || ctype_digit($code) === false || strlen($code) !== 6) {
            throw new InvalidArgumentException(
                "Enter the full 6-digit code."
            );
        }

        $reset = $this->passwordResetRepo->getActiveByEmail($email);

        if ($reset === false) {
            throw new InvalidArgumentException(
                "This code is no longer valid. Please request a new one."
            );
        }

        $resetId = (int) $reset["id"];
        $codeHash = (string) $reset["code_hash"];

        if (password_verify($code, $codeHash) === false) {
            $this->passwordResetRepo->bumpAttempts($resetId);

            if (
                (int) $reset["attempts"] + 1
                    >= PasswordResetRepo::MAX_ATTEMPTS
            ) {
                throw new InvalidArgumentException(
                    "Too many attempts. Request a new code."
                );
            }

            throw new InvalidArgumentException(
                "The code you entered is incorrect."
            );
        }

        // One-time token (32 random bytes) — only its sha-256 hash
        // is persisted, and only the frontend receives it.
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash("sha256", $token);

        $this->passwordResetRepo->markVerified(
            $resetId,
            $tokenHash,
            self::TOKEN_TTL_MINUTES
        );

        // Any other pending code row for this email is dead now.
        $this->passwordResetRepo->invalidatePending($email);

        return $token;
    }

    // =========================================
    // STEP 3 — SET THE NEW PASSWORD
    // =========================================

    /**
     * Applies the new password once, then sends the
     * "your password was changed" confirmation email.
     *
     * @throws InvalidArgumentException on an invalid token/password.
     * @throws RuntimeException when saving fails.
     */
    public function completeReset(
        string $token,
        string $password,
        string $passwordConfirmation
    ): bool {
        $token = trim($token);

        if ($token === "" || strlen($password) < 8) {
            throw new InvalidArgumentException(
                "Password must be at least 8 characters."
            );
        }

        if ($password !== $passwordConfirmation) {
            throw new InvalidArgumentException(
                "Passwords do not match."
            );
        }

        $tokenHash = hash("sha256", $token);
        $reset = $this->passwordResetRepo->getByTokenHash($tokenHash);

        if ($reset === false) {
            throw new InvalidArgumentException(
                "This reset link is invalid or has expired. Request a new code."
            );
        }

        $resetId = (int) $reset["id"];
        $userId = (int) $reset["user_id"];
        $newHash = password_hash($password, PASSWORD_DEFAULT);

        $updated = $this->authRepo->updatePassword($userId, $newHash);

        if (!$updated) {
            throw new RuntimeException("Failed to update the password.");
        }

        $this->passwordResetRepo->markUsed($resetId);

        // Security confirmation — same toggle + delivery log as
        // every other notification, with its dedicated template.
        try {
            $appBaseUrl = $this->appBaseUrl();

            $userRow = $this->authRepo->findUserById($userId);
            $username = is_array($userRow)
                ? (string) ($userRow["username"] ?? "there")
                : "there";

            $this->notificationService->sendTemplate(
                $userId,
                NotificationService::TYPE_PASSWORD_RESET,
                "Your password was changed",
                function () use ($username, $appBaseUrl): array {
                    return [
                        EmailTemplates::renderPasswordChanged(
                            $username,
                            $appBaseUrl
                        ),
                        "=== Your password was changed ===\n\n"
                        . "Your Inventory Hub account password was just changed.\n\n"
                        . "If this was you, no action is needed. If you did NOT change your password, contact an administrator immediately.\n\n"
                        . "- Inventory Hub",
                    ];
                }
            );
        } catch (Throwable $error) {
            // never block the reset on the confirmation email
            error_log(
                "[PasswordReset] Confirmation email failed: "
                . $error->getMessage()
            );
        }

        return true;
    }

    // =========================================
    // HELPERS
    // =========================================

    private function generateCode(): string
    {
        // 6_digits, random, evenly spread over 000000..999999.
        return str_pad(
            (string) random_int(0, 999999),
            6,
            "0",
            STR_PAD_LEFT
        );
    }

    private function appBaseUrl(): string
    {
        $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
            ? "https"
            : "http";

        $host = $_SERVER["HTTP_HOST"] ?? "localhost";

        return $scheme . "://" . $host;
    }
}

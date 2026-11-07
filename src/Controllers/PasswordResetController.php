<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\PasswordResetService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Endpoints for the forget/reset password flow.
 *
 *      POST /api/auth/password-reset/request   {email}
 *      POST /api/auth/password-reset/verify    {email, code} -> {token}
 *      POST /api/auth/password-reset/reset     {token, password,
 *                                               password_confirmation}
 *
 * Authenticated variants used by the Settings page (no email is
 * accepted from the client — the session account is used):
 *
 *      POST /settings/password-reset/request   {}            -> {masked_email}
 *      POST /settings/password-reset/verify    {code}        -> {token}
 *      POST /settings/password-reset/reset     {token, password,
 *                                               password_confirmation}
 *
 * All responses are JSON. "request" always answers the same generic
 * message so the endpoint can't be used to discover which emails
 * are registered.
 */
class PasswordResetController
{
    private PasswordResetService $passwordResetService;

    private AuthService $authService;

    public function __construct(
        PasswordResetService $passwordResetService,
        AuthService $authService
    ) {
        $this->passwordResetService = $passwordResetService;
        $this->authService = $authService;
    }

    // =========================================
    // STEP 1 — SEND CODE
    // =========================================

    public function requestReset(): void
    {
        $this->handle(
            function (array $data): array {
                $this->requireCsrf();

                $this->passwordResetService->requestReset(
                    (string) ($data["email"] ?? ""),
                    $this->appBaseUrl()
                );

                // Unregistered emails are rejected with an explicit
                // error (owner-requested: only registered emails can
                // reset). InvalidArgumentException propagates the
                // message to the frontend via handle().
                return [
                    "ok" => true,
                    "message" => "If notification emails are enabled, your 6-digit code is on its way."
                ];
            }
        );
    }

    // =========================================
    // STEP 2 — VERIFY CODE
    // =========================================

    public function verifyCode(): void
    {
        $this->handle(
            function (array $data): array {
                $this->requireCsrf();

                $token = $this->passwordResetService->verifyCode(
                    (string) ($data["email"] ?? ""),
                    (string) ($data["code"] ?? "")
                );

                return [
                    "ok" => true,
                    "token" => $token
                ];
            }
        );
    }

    // =========================================
    // STEP 3 — SET NEW PASSWORD
    // =========================================

    public function reset(): void
    {
        $this->handle(
            function (array $data): array {
                $this->requireCsrf();

                $this->passwordResetService->completeReset(
                    (string) ($data["token"] ?? ""),
                    (string) ($data["password"] ?? ""),
                    (string) ($data["password_confirmation"] ?? "")
                );

                return [
                    "ok" => true,
                    "message" => "Password updated successfully."
                ];
            }
        );
    }

    // =========================================
    // AUTHENTICATED / SETTINGS VARIANTS
    // =========================================

    /**
     * Sends a code to the signed-in account's email address.
     * The client never supplies an email address here.
     */
    public function requestResetForCurrentUser(): void
    {
        $this->handle(
            function (array $data): array {
                $this->requireCsrf();

                $email = $this->currentUserEmail();

                $this->passwordResetService->requestReset(
                    $email,
                    $this->appBaseUrl()
                );

                $masked = $this->maskEmail($email);

                return [
                    "ok" => true,
                    "message" => "A 6-digit code was sent to {$masked}.",
                    "masked_email" => $masked
                ];
            }
        );
    }

    /**
     * Verifies a code for the signed-in account and returns the
     * one-time reset token used by the final step.
     */
    public function verifyCodeForCurrentUser(): void
    {
        $this->handle(
            function (array $data): array {
                $this->requireCsrf();

                $token = $this->passwordResetService->verifyCode(
                    $this->currentUserEmail(),
                    (string) ($data["code"] ?? "")
                );

                return [
                    "ok" => true,
                    "token" => $token
                ];
            }
        );
    }

    // =========================================
    // SHARED HANDLING
    // =========================================

    private function handle(callable $action): void
    {
        try {
            $this->json(
                $action($this->requestData())
            );
        } catch (InvalidArgumentException $e) {
            $this->json(
                ["ok" => false, "error" => $e->getMessage()],
                400
            );
        } catch (RuntimeException $e) {
            error_log(
                "[PasswordReset] Runtime failure: "
                . $e->getMessage()
            );

            $this->json(
                [
                    "ok" => false,
                    "error" => "Something went wrong. Please try again."
                ],
                500
            );
        } catch (Throwable $e) {
            error_log(
                "[PasswordReset] Unexpected failure: "
                . $e->getMessage()
            );

            $this->json(
                [
                    "ok" => false,
                    "error" => "Something went wrong. Please try again."
                ],
                500
            );
        }
    }

    // =========================================
    // HELPERS
    // =========================================

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);

        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store");
        header("X-Content-Type-Options: nosniff");

        echo json_encode(
            $data,
            JSON_THROW_ON_ERROR
        );
    }

    private function requestData(): array
    {
        $body = file_get_contents("php://input");

        if ($body === false || strlen($body) > 262144) {
            throw new RuntimeException("Invalid request size.");
        }

        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException("Invalid request.");
        }

        return $data;
    }

    private function requireCsrf(): void
    {
        $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

        if (!$this->authService->verifyCsrfToken($token)) {
            throw new InvalidArgumentException(
                "Your session expired. Please reload the page and try again."
            );
        }
    }

    /**
     * Email of the signed-in account (never taken from the request).
     */
    private function currentUserEmail(): string
    {
        $user = $this->authService->getCurrentUser();

        if (!is_array($user)) {
            throw new InvalidArgumentException(
                "Please log in again to continue."
            );
        }

        $email = strtolower(trim((string) ($user["email"] ?? "")));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                "Your account has no valid email address."
            );
        }

        return $email;
    }

    /**
     * "jdoe@example.com" -> "j•••@example.com" for UI display.
     */
    private function maskEmail(string $email): string
    {
        $parts = explode("@", $email, 2);

        if (count($parts) !== 2 || $parts[0] === "" || $parts[1] === "") {
            return $email;
        }

        [$local, $domain] = $parts;

        $visible = mb_substr($local, 0, 1);
        $hidden = str_repeat(
            "•",
            max(2, mb_strlen($local) - 1)
        );

        return $visible . $hidden . "@" . $domain;
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

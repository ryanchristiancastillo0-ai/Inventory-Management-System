<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AuthService;
use App\Services\ProfileService;
use App\Services\ActivityLogService;
use App\Services\SmsService;
use App\Repositories\SmsOtpRepo;
use App\Exceptions\ProfileValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ProfileController
{
    public function __construct(
        private ProfileService $profileService,
        private AuthService $authService,
        private ActivityLogService $activityLogService,
        private SmsService $smsService,
        private SmsOtpRepo $smsOtpRepo
    ) {
    }

    private function currentUser(): array
    {
        $user = $this->authService->getCurrentUser();
        if (!$user || (int) ($user['id'] ?? 0) <= 0) {
            http_response_code(401);
            exit('Please log in again.');
        }
        if (!in_array($user['role'] ?? '', ['ADMIN', 'USER'], true)) {
            http_response_code(403);
            exit('Access denied.');
        }
        return $user;
    }

    public function index(): void
    {
        $user = $this->currentUser();
        try {
            $profile = $this->profileService->getProfile((int) $user['id']);
        } catch (Throwable $error) {
            error_log((string) $error);
            http_response_code(503);
            exit('Unable to load your profile. Please try again.');
        }
        if ($profile === null) {
            http_response_code(404);
            exit('Profile not found.');
        }

        $flash = $_SESSION['profile_flash'] ?? null;
        $old = $_SESSION['profile_old'] ?? [];
        $errors = $_SESSION['profile_errors'] ?? [];
        unset($_SESSION['profile_flash'], $_SESSION['profile_old'], $_SESSION['profile_errors']);

        $security = null;
        $activity = null;
        try {
            $security = $this->profileService->getSecuritySummary((int) $user['id']);
        } catch (Throwable $error) {
            error_log((string) $error);
        }
        try {
            $activity = $this->activityLogService->getLogsByUserId((int) $user['id'], 5);
        } catch (Throwable $error) {
            error_log((string) $error);
        }

        View::render('profile/index', [
            'title' => 'Profile',
            'profile' => $profile,
            'flash' => $flash,
            'old' => $old,
            'errors' => $errors,
            'editing' => ($_GET['edit'] ?? '') === '1' || $old !== [],
            'security' => $security,
            'activity' => $activity,
            'csrfToken' => $this->authService->getCsrfToken(),
            'topbarUserName' => $profile['username'],
            'topbarUserInitials' => mb_strtoupper(mb_substr($profile['username'], 0, 1))
        ], $user['role'] === 'ADMIN' ? 'admin' : 'app');
    }

    public function update(): void
    {
        $user = $this->currentUser();
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !$this->authService->verifyCsrfToken($token)) {
            http_response_code(403);
            exit('Your session expired. Reload the profile page and try again.');
        }

        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $_SESSION['profile_old'] = [
            'username' => is_string($username) ? $username : '',
            'email' => is_string($email) ? $email : ''
        ];

        try {
            if (!is_string($username) || !is_string($email)) {
                throw new InvalidArgumentException('Enter a valid username and email address.');
            }
            // Identity comes exclusively from the authenticated session.
            if (!$this->profileService->updateProfile((int) $user['id'], $username, $email)) {
                throw new RuntimeException('Profile update failed.');
            }
            $_SESSION['username'] = trim($username);
            unset($_SESSION['profile_old'], $_SESSION['profile_errors']);
            $_SESSION['profile_flash'] = ['type' => 'success', 'message' => 'Your profile has been updated.'];
        } catch (ProfileValidationException $error) {
            $_SESSION['profile_errors'] = $error->getErrors();
            $_SESSION['profile_flash'] = ['type' => 'danger', 'message' => 'Please check the highlighted fields.'];
        } catch (InvalidArgumentException $error) {
            $_SESSION['profile_flash'] = ['type' => 'danger', 'message' => $error->getMessage()];
        } catch (Throwable $error) {
            error_log((string) $error);
            $_SESSION['profile_flash'] = ['type' => 'danger', 'message' => 'Unable to save your profile. Please try again.'];
        }

        header('Location: /profile', true, 303);
        exit;
    }

    // =============================
    // PHONE NUMBER VERIFICATION
    // =============================

    /**
     * POST /profile/phone/request (JSON)
     * Sends an OTP to a NEW mobile number to prove ownership.
     * Requires an authenticated user.
     */
    public function requestPhoneVerification(): void
    {
        try {
            $user = $this->currentUser();
            $this->requireJsonCsrf();

            $raw = trim((string) ($_POST['phone'] ?? ''));
            $phone = $this->smsService->normalizePhoneNumber($raw);

            if (!$this->smsService->isValidPhilippineNumber($phone)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Enter a valid Philippine mobile number (e.g., 09123456789).',
                ], 400);
            }

            // Ownership protection: the number must not already belong
            // to ANOTHER user with a verified claim.
            if ($this->smsOtpRepo->isPhoneVerifiedForAnotherUser($phone, (int) $user['id'])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'This mobile number is already linked to another account.',
                ], 409);
            }

            // Rate limit + resend cooldown reuse the same repo.
            $rateLimit = $this->smsOtpRepo->checkRateLimit((int) $user['id']);
            if (!$rateLimit['allowed']) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Too many SMS requests. Please try again later.',
                ], 429);
            }

            $cooldown = $this->smsOtpRepo->checkResendCooldown((int) $user['id'], $phone);
            if (!$cooldown['allowed']) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Please wait ' . $cooldown['secondsRemaining'] . ' seconds before requesting another code.',
                    'cooldown' => $cooldown['secondsRemaining'],
                ], 429);
            }

            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $codeHash = password_hash($otp, PASSWORD_DEFAULT);

            $challengeId = $this->smsOtpRepo->create(
                (int) $user['id'],
                $phone,
                $codeHash,
                SmsOtpRepo::OTP_TTL_MINUTES
            );

            $result = $this->smsService->send(
                $phone,
                "Your verification code is {$otp}. It expires in "
                    . SmsOtpRepo::OTP_TTL_MINUTES . " minutes."
            );

            if (!$result['success']) {
                // Invalidate the challenge so the failed send cannot verify later.
                $this->smsOtpRepo->invalidatePending((int) $user['id'], $phone);
                error_log('[Profile] SMS send failed: ' . $result['message']
                    . ' (code: ' . ($result['errorCode'] ?? 'UNKNOWN') . ')');

                // Unsupported-network rejections explain themselves;
                // every other failure stays generic.
                $this->jsonResponse([
                    'success' => false,
                    'message' => ($result['errorCode'] ?? '') === 'UNSUPPORTED_NETWORK'
                        ? $result['message']
                        : 'Unable to send the SMS at this time. Please try again later.',
                ], 502);
            }

            $_SESSION['profile_phone_challenge_id'] = $challengeId;
            $_SESSION['profile_phone_pending'] = $phone;

            $this->jsonResponse([
                'success' => true,
                'message' => 'A code was sent to your mobile number.',
                'cooldown' => SmsOtpRepo::RESEND_COOLDOWN_SECONDS,
            ]);
        } catch (Throwable $error) {
            error_log((string) $error);
            $this->jsonResponse([
                'success' => false,
                'message' => 'Unable to start phone verification. Please try again.',
            ], 500);
        }
    }

    /**
     * POST /profile/phone/verify (JSON)
     * Verifies the OTP and marks the number verified — only then can
     * it be used for SMS login.
     */
    public function verifyPhoneVerification(): void
    {
        try {
            $user = $this->currentUser();
            $this->requireJsonCsrf();

            $challengeId = (int) ($_SESSION['profile_phone_challenge_id'] ?? 0);
            $pendingPhone = (string) ($_SESSION['profile_phone_pending'] ?? '');
            $code = trim((string) ($_POST['code'] ?? ''));

            if ($challengeId <= 0 || $pendingPhone === '') {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'No pending verification. Request a new code.',
                ], 400);
            }

            if ($code === '' || !ctype_digit($code) || strlen($code) !== 6) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Enter the full 6-digit code.',
                ], 400);
            }

            $challenge = $this->smsOtpRepo->getActiveById($challengeId);

            if (!$challenge
                || (string) $challenge['phone'] !== $pendingPhone
                || (int) $challenge['user_id'] !== (int) $user['id']
            ) {
                $this->clearPendingPhoneChallenge();
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'This code is no longer valid. Please request a new one.',
                ], 400);
            }

            if ((int) $challenge['attempts'] + 1 >= SmsOtpRepo::MAX_ATTEMPTS
                && !password_verify($code, (string) $challenge['code_hash'])
            ) {
                $this->smsOtpRepo->bumpAttempts($challengeId);
                $this->clearPendingPhoneChallenge();
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Too many failed attempts. Please request a new code.',
                ], 400);
            }

            if (!password_verify($code, (string) $challenge['code_hash'])) {
                $this->smsOtpRepo->bumpAttempts($challengeId);
                $left = SmsOtpRepo::MAX_ATTEMPTS - ((int) $challenge['attempts'] + 1);
                $this->jsonResponse([
                    'success' => false,
                    'message' => $left > 0
                        ? 'Incorrect code. ' . $left . ' attempt(s) remaining.'
                        : 'Incorrect code. Please request a new one.',
                ], 400);
            }

            // Correct: verify the challenge, then persist ownership.
            $this->smsOtpRepo->markVerified($challengeId);
            $this->smsOtpRepo->markUsed($challengeId);

            if (!$this->smsOtpRepo->updateUserPhone((int) $user['id'], $pendingPhone, true)) {
                throw new RuntimeException('Failed to save the verified mobile number.');
            }

            $this->clearPendingPhoneChallenge();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Your mobile number is verified. You can now sign in with it.',
                'phoneNumber' => $pendingPhone,
            ]);
        } catch (Throwable $error) {
            error_log((string) $error);
            $this->jsonResponse([
                'success' => false,
                'message' => 'Unable to verify your mobile number. Please try again.',
            ], 500);
        }
    }

    // =============================
    // HELPERS
    // =============================

    private function clearPendingPhoneChallenge(): void
    {
        unset($_SESSION['profile_phone_challenge_id'], $_SESSION['profile_phone_pending']);
    }

    /**
     * CSRF validation for JSON/POST body requests (token field or header).
     */
    private function requireJsonCsrf(): void
    {
        $token = $_POST['csrf_token']
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        if (!is_string($token) || !$this->authService->verifyCsrfToken($token)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Your session expired. Reload the page and try again.',
            ], 403);
        }
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuthRepo;
use App\Repositories\SmsOtpRepo;
use InvalidArgumentException;
use RuntimeException;

/**
 * SMS OTP Authentication Service
 *
 * Handles the complete SMS OTP login flow:
 * - Request OTP for a verified phone number
 * - Verify OTP code
 * - Resend OTP with cooldown
 * - Rate limiting
 *
 * Uses bcrypt-hashed OTP codes, constant-time comparison,
 * and secure session management.
 */
class SmsAuthService
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const OTP_LENGTH = 6;
    private const OTP_TTL_MINUTES = 5;

    private AuthRepo $authRepo;
    private SmsOtpRepo $smsOtpRepo;
    private SmsService $smsService;

    public function __construct(
        AuthRepo $authRepo,
        SmsOtpRepo $smsOtpRepo,
        SmsService $smsService
    ) {
        $this->authRepo = $authRepo;
        $this->smsOtpRepo = $smsOtpRepo;
        $this->smsService = $smsService;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // =========================================
    // STEP 1: REQUEST OTP (Send SMS)
    // =========================================

    /**
     * Request an OTP to be sent via SMS.
     *
     * @param string $phone Phone number in any supported format
     *
     * @return array ['success' => bool, 'message' => string, 'challengeId' => int|null, 'cooldown' => int|null]
     */
    public function requestOtp(string $phone): array
    {
        // Normalize phone number
        $normalizedPhone = $this->smsService->normalizePhoneNumber($phone);

        // Validate phone format
        if (!$this->smsService->isValidPhilippineNumber($normalizedPhone)) {
            return [
                'success' => false,
                'message' => 'Please enter a valid Philippine mobile number (e.g., 09123456789).',
                'challengeId' => null,
                'cooldown' => null,
            ];
        }

        // Check if phone is verified for an existing user
        $user = $this->smsOtpRepo->findUserByVerifiedPhone($normalizedPhone);

        if (!$user) {
            // Don't reveal whether the number exists - use generic message
            // But we still need to return a "success" looking response to prevent enumeration
            // The actual SMS won't be sent, but we pretend it was
            return [
                'success' => true,
                'message' => 'If this number is registered and verified, a code has been sent.',
                'challengeId' => null,
                'cooldown' => null,
            ];
        }

        $userId = (int) $user['id'];

        // Check rate limit (max 10 requests per hour per user)
        $rateLimit = $this->smsOtpRepo->checkRateLimit($userId);
        if (!$rateLimit['allowed']) {
            return [
                'success' => false,
                'message' => 'Too many SMS requests. Please try again later.',
                'challengeId' => null,
                'cooldown' => $rateLimit['windowMinutes'] * 60,
            ];
        }

        // Check resend cooldown
        $cooldown = $this->smsOtpRepo->checkResendCooldown($userId, $normalizedPhone);
        if (!$cooldown['allowed']) {
            return [
                'success' => false,
                'message' => 'Please wait ' . $cooldown['secondsRemaining'] . ' seconds before requesting another code.',
                'challengeId' => null,
                'cooldown' => $cooldown['secondsRemaining'],
            ];
        }

        // Generate cryptographically secure 6-digit OTP
        $otp = $this->generateOtp();
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

        // Create challenge in database
        $challengeId = $this->smsOtpRepo->create(
            $userId,
            $normalizedPhone,
            $otpHash,
            self::OTP_TTL_MINUTES
        );

        // Send SMS via UniSMS
        $message = "Your verification code is {$otp}. It expires in " . self::OTP_TTL_MINUTES . " minutes.";
        $smsResult = $this->smsService->send($normalizedPhone, $message);

        if (!$smsResult['success']) {
            // If SMS fails, invalidate the challenge.
            // Unsupported-network rejections get a specific explanation;
            // everything else stays generic so raw provider errors
            // never reach the user.
            error_log('[SmsAuth] SMS send failed: ' . $smsResult['message'] . ' (Code: ' . $smsResult['errorCode'] . ')');

            return [
                'success' => false,
                'message' => ($smsResult['errorCode'] ?? '') === 'UNSUPPORTED_NETWORK'
                    ? $smsResult['message']
                    : 'Unable to send SMS at this time. Please try again later.',
                'challengeId' => null,
                'cooldown' => null,
            ];
        }

        // Store challenge ID in session for verification step
        $_SESSION['sms_otp_challenge_id'] = $challengeId;
        $_SESSION['sms_otp_phone'] = $normalizedPhone;
        $_SESSION['sms_otp_user_id'] = $userId;

        return [
            'success' => true,
            'message' => 'Verification code sent to ' . $this->maskPhoneNumber($normalizedPhone) . '.',
            'challengeId' => $challengeId,
            'cooldown' => SmsOtpRepo::RESEND_COOLDOWN_SECONDS,
        ];
    }

    // =========================================
    // STEP 2: VERIFY OTP
    // =========================================

    /**
     * Verify the OTP code entered by the user.
     *
     * @param string $code 6-digit code entered by user
     *
     * @return array ['success' => bool, 'message' => string, 'redirect' => string|null]
     */
    public function verifyOtp(string $code): array
    {
        $challengeId = (int) ($_SESSION['sms_otp_challenge_id'] ?? 0);
        $phone = $_SESSION['sms_otp_phone'] ?? '';
        $userId = (int) ($_SESSION['sms_otp_user_id'] ?? 0);

        if ($challengeId <= 0 || $phone === '' || $userId <= 0) {
            return [
                'success' => false,
                'message' => 'No active verification session. Please request a new code.',
                'redirect' => null,
            ];
        }

        // Validate code format
        $code = trim($code);
        if ($code === '' || !ctype_digit($code) || strlen($code) !== self::OTP_LENGTH) {
            return [
                'success' => false,
                'message' => 'Please enter the full 6-digit code.',
                'redirect' => null,
            ];
        }

        // Get active challenge
        $challenge = $this->smsOtpRepo->getActiveById($challengeId);

        if (!$challenge) {
            // Clean up session
            $this->clearOtpSession();

            return [
                'success' => false,
                'message' => 'This code has expired or was already used. Please request a new one.',
                'redirect' => null,
            ];
        }

        // Verify the code matches the phone in session
        if ($challenge['phone'] !== $phone || (int) $challenge['user_id'] !== $userId) {
            $this->clearOtpSession();

            return [
                'success' => false,
                'message' => 'Invalid verification session. Please start over.',
                'redirect' => null,
            ];
        }

        // Check attempts
        $attempts = (int) $challenge['attempts'];
        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            $this->clearOtpSession();

            return [
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new code.',
                'redirect' => null,
            ];
        }

        // Verify code using constant-time comparison (password_verify)
        if (!password_verify($code, $challenge['code_hash'])) {
            // Increment attempts
            $this->smsOtpRepo->bumpAttempts($challengeId);

            $remaining = self::MAX_FAILED_ATTEMPTS - $attempts - 1;

            return [
                'success' => false,
                'message' => $remaining > 0
                    ? "Incorrect code. {$remaining} attempt(s) remaining."
                    : 'Too many failed attempts. Please request a new code.',
                'redirect' => null,
            ];
        }

        // Code is correct - mark as verified
        $this->smsOtpRepo->markVerified($challengeId);

        // Get user data for login
        $user = $this->authRepo->findUserById($userId);

        if (!$user) {
            $this->clearOtpSession();

            return [
                'success' => false,
                'message' => 'User account not found.',
                'redirect' => null,
            ];
        }

        // Deactivated accounts cannot log in, even with a
        // correct OTP code.
        if ((int) ($user['is_active'] ?? 1) !== 1) {
            $this->clearOtpSession();

            return [
                'success' => false,
                'message' => 'Your account has been deactivated. Contact an administrator.',
                'redirect' => null,
            ];
        }

        // Create login session (regenerate ID for security)
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Prevent reuse of pre-login CSRF token
        unset($_SESSION['csrf_token']);

        // Clear OTP session data
        $this->clearOtpSession();

        // Mark challenge as used
        $this->smsOtpRepo->markUsed($challengeId);

        // Determine redirect based on role
        $redirect = ($user['role'] ?? '') === 'ADMIN'
            ? '/admin/dashboard'
            : '/dashboard';

        return [
            'success' => true,
            'message' => 'Login successful.',
            'redirect' => $redirect,
        ];
    }

    // =========================================
    // RESEND OTP
    // =========================================

    /**
     * Resend OTP code (with cooldown check).
     *
     * @return array ['success' => bool, 'message' => string, 'cooldown' => int|null]
     */
    public function resendOtp(): array
    {
        $phone = $_SESSION['sms_otp_phone'] ?? '';
        $userId = (int) ($_SESSION['sms_otp_user_id'] ?? 0);

        if ($phone === '' || $userId <= 0) {
            return [
                'success' => false,
                'message' => 'No active verification session. Please request a new code.',
                'cooldown' => null,
            ];
        }

        // This will handle cooldown and rate limiting internally
        return $this->requestOtp($phone);
    }

    // =========================================
    // HELPERS
    // =========================================

    /**
     * Generate a cryptographically secure 6-digit OTP.
     */
    private function generateOtp(): string
    {
        return str_pad(
            (string) random_int(0, 999999),
            self::OTP_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Mask phone number for display (show last 4 digits).
     */
    private function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) >= 4) {
            return '***-***-' . substr($phone, -4);
        }
        return $phone;
    }

    /**
     * Clear OTP session data.
     */
    public function clearOtpSession(): void
    {
        unset(
            $_SESSION['sms_otp_challenge_id'],
            $_SESSION['sms_otp_phone'],
            $_SESSION['sms_otp_user_id']
        );
    }

    /**
     * Check if there's an active OTP session.
     */
    public function hasActiveOtpSession(): bool
    {
        return !empty($_SESSION['sms_otp_challenge_id'])
            && !empty($_SESSION['sms_otp_phone'])
            && !empty($_SESSION['sms_otp_user_id']);
    }

    /**
     * Get masked phone number for current session.
     */
    public function getMaskedPhone(): string
    {
        $phone = $_SESSION['sms_otp_phone'] ?? '';
        return $this->maskPhoneNumber($phone);
    }

    /**
     * Get remaining time for OTP expiration (in seconds).
     */
    public function getOtpTimeRemaining(): int
    {
        $challengeId = (int) ($_SESSION['sms_otp_challenge_id'] ?? 0);

        if ($challengeId <= 0) {
            return 0;
        }

        $challenge = $this->smsOtpRepo->getActiveById($challengeId);

        if (!$challenge) {
            return 0;
        }

        $expiresAt = strtotime($challenge['expires_at']);
        $remaining = $expiresAt - time();

        return max(0, $remaining);
    }
}
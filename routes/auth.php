<?php

/** @var string $route */
declare(strict_types=1);

// ==========================================
// AUTHENTICATION ROUTES
// ==========================================

switch ($route) {

    // ======================================
    // AUTH PAGES
    // ======================================

    case "GET /auth":

        $authController->showAuth();
        exit;

    case "GET /login":

        $authController->showLogin();
        exit;

    case "GET /register":

        $authController->showRegister();
        exit;

    case "GET /forgot-password":

        $authController->showForgotPassword();
        exit;

    case "GET /reset-password":

        $authController->showResetPassword();
        exit;


    // ======================================
    // PASSWORD RESET API (FORGOT PASSWORD FLOW)
    // ======================================

    case "POST /api/auth/password-reset/request":

        $passwordResetController->requestReset();
        exit;

    case "POST /api/auth/password-reset/verify":

        $passwordResetController->verifyCode();
        exit;

    case "POST /api/auth/password-reset/reset":

        $passwordResetController->reset();
        exit;


    // ======================================
    // SMS OTP LOGIN API
    // ======================================

    case "POST /api/auth/sms/request":

        $authController->requestSmsOtp();
        exit;

    case "POST /api/auth/sms/verify":

        $authController->verifySmsOtp();
        exit;

    case "POST /api/auth/sms/resend":

        $authController->resendSmsOtp();
        exit;


    // ======================================
    // AUTH ACTIONS
    // ======================================

    case "POST /register":

        $authController->register();
        exit;

    case "POST /login":

        $authController->login();
        exit;

    case "POST /logout":

        $authMiddleware->handle();

        $authController->logout();
        exit;


    // ======================================
    // CURRENT AUTHENTICATED USER
    // ======================================

    case "GET /me":

        // $authMiddleware->handle();

        $user = $authService->getCurrentUser();

        if (!$user) {

            jsonResponse([
                "error" => "User not found"
            ], 404);
        }

        unset($user["password_hash"]);

        jsonResponse($user);


    // ======================================
    // PASSKEY REGISTRATION
    // ======================================

    case "POST /passkey/register/options":

        $authMiddleware->handle();

        $passkeyController->registrationOptions();
        exit;

    case "POST /passkey/register/verify":

        $authMiddleware->handle();

        $passkeyController->verifyRegistration();
        exit;


    // ======================================
    // PASSKEY LOGIN
    // ======================================

    case "POST /passkey/login/options":

        $passkeyController->loginOptions();
        exit;

    case "POST /passkey/login/verify":

        $passkeyController->verifyLogin();
        exit;
}

<?php

declare(strict_types=1);

/** @var string $route */
/** @var \App\Middleware\AuthMiddleware $authMiddleware */
/** @var \App\Controllers\SettingsController $settingsController */
/** @var \App\Controllers\PasswordResetController $passwordResetController */

// ==========================================
// SHARED SETTINGS ROUTES
// ADMIN AND USER
// ==========================================

switch ($route) {

    // ======================================
    // SETTINGS PAGE
    // ======================================

    case "GET /settings":

        $authMiddleware->handle();

        $settingsController->index();
        exit;


    // ======================================
    // UPDATE APPEARANCE AND NOTIFICATIONS
    // ======================================

    case "POST /settings/preferences":

        $authMiddleware->handle();

        $settingsController->updatePreferences();
        exit;


    // ======================================
    // UPDATE ACCOUNT PROFILE
    // ======================================

    case "POST /settings/profile":

        $authMiddleware->handle();

        $settingsController->updateProfile();
        exit;


    // ======================================
    // CHANGE ACCOUNT PASSWORD
    // ======================================

    case "POST /settings/password":
    
        $authMiddleware->handle();
    
        $settingsController->changePassword();
        exit;


    // ======================================
    // FORGOT PASSWORD (SIGNED-IN USER)
    // The account email always comes from the session,
    // never from the request body.
    // ======================================

    case "POST /settings/password-reset/request":

        $authMiddleware->handle();

        $passwordResetController->requestResetForCurrentUser();
        exit;

    case "POST /settings/password-reset/verify":

        $authMiddleware->handle();

        $passwordResetController->verifyCodeForCurrentUser();
        exit;

    case "POST /settings/password-reset/reset":

        $authMiddleware->handle();

        $passwordResetController->reset();
        exit;
}

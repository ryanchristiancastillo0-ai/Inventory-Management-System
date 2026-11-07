<?php

declare(strict_types=1);

/** @var string $route */
/** @var \App\Middleware\AuthMiddleware $authMiddleware */
/** @var \App\Controllers\ProfileController $profileController */

switch ($route) {
    case 'GET /profile':
        $authMiddleware->handle();
        $profileController->index();
        exit;

    case 'POST /profile':
        $authMiddleware->handle();
        $profileController->update();
        exit;

    // ======================================
    // PHONE NUMBER VERIFICATION (SMS OTP)
    // ======================================

    case 'POST /profile/phone/request':
        $authMiddleware->handle();
        $profileController->requestPhoneVerification();
        exit;

    case 'POST /profile/phone/verify':
        $authMiddleware->handle();
        $profileController->verifyPhoneVerification();
        exit;
}

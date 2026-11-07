<?php

declare(strict_types=1);

/** @var string $route */
/** @var \App\Middleware\AuthMiddleware $authMiddleware */
/** @var \App\Controllers\FaceController $faceController */

// ==========================================
// FACE RECOGNITION ROUTES
// ==========================================

switch ($route) {

    // ======================================
    // BEGIN FACE LOGIN (public, pre-login)
    // ======================================

    case "GET /face/login-options":

        $faceController->loginOptions();
        exit;


    // ======================================
    // VERIFY FACE LOGIN (public, pre-login)
    // ======================================

    case "POST /face/verify":

        $faceController->verifyLogin();
        exit;


    // ======================================
    // CHOOSE ACCOUNT (multi-face match)
    // ======================================

    case "POST /face/login/select":

        $faceController->selectAccount();
        exit;


    // ======================================
    // LIST MY ENROLLMENTS (logged in)
    // ======================================

    case "GET /face/enrollments":

        $authMiddleware->handle();

        $faceController->index();
        exit;


    // ======================================
    // ENROLL MY FACES (logged in)
    // ======================================

    case "POST /face/enroll":

        $authMiddleware->handle();

        $faceController->enroll();
        exit;


    // ======================================
    // DELETE MY FACES (logged in)
    // ======================================

    case "POST /face/delete":

        $authMiddleware->handle();

        $faceController->delete();
        exit;
}
<?php

declare(strict_types=1);

/** @var string $route */
/** @var \App\Middleware\AuthMiddleware $authMiddleware */
/** @var \App\Middleware\RoleMiddleware $roleMiddleware */
/** @var \App\Controllers\AdminDashboardController $adminDashboardController */
/** @var \App\Controllers\AdminUserController $adminUserController */

// ==========================================
// ADMIN ROUTES
// ==========================================

switch ($route) {

    // ======================================
    // ADMIN DASHBOARD
    // ======================================

    case "GET /admin/dashboard":

        $authMiddleware->handle();
        $roleMiddleware->handle("ADMIN");

        $adminDashboardController->showDashboard();
        exit;


        // ======================================
        // USER MANAGEMENT PAGE
        // ======================================

    case "GET /admin/users":

        $authMiddleware->handle();
        $roleMiddleware->handle("ADMIN");

        $adminUserController->index();
        exit;


        // ======================================
        // CREATE USER
        // ======================================

    case "POST /admin/users/create":

        $authMiddleware->handle();
        $roleMiddleware->handle("ADMIN");

        $adminUserController->store();
        exit;


        // ======================================
        // UPDATE USER
        // ======================================

    case "POST /admin/users/update":

        $authMiddleware->handle();
        $roleMiddleware->handle("ADMIN");

        $adminUserController->update();
        exit;


        // ======================================
        // DELETE USER
        // ======================================

    case "POST /admin/users/delete":

        $authMiddleware->handle();
        $roleMiddleware->handle("ADMIN");

        $adminUserController->destroy();
        exit;




    case "GET /admin/activity-logs":

        $authMiddleware->handle();

        $roleMiddleware->handle("ADMIN");

        $adminActivityLogController->index();
        exit;


        // ==========================================
// ADMIN REPORTS MANAGEMENT PAGE
// ==========================================

case "GET /admin/reports":

    $authMiddleware->handle();
    $roleMiddleware->handle("ADMIN");

    $adminReportController->index();
    exit;


// ==========================================
// UPDATE REPORT STATUS / RESPONSE
// ==========================================

case "POST /admin/reports/update":

    $authMiddleware->handle();
    $roleMiddleware->handle("ADMIN");

    $adminReportController->update();
    exit;
}

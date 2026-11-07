<?php

declare(strict_types=1);

/** @var string $route */
/** @var \App\Middleware\AuthMiddleware $authMiddleware */
/** @var \App\Controllers\NotificationController $notificationController */

// ==========================================
// NOTIFICATIONS (EMAIL DELIVERY LOG)
// ==========================================

switch ($route) {

    // ======================================
    // LIST MY EMAIL NOTIFICATIONS
    // ======================================

    case "GET /notifications":

        $authMiddleware->handle();

        $notificationController->index();
        exit;
}

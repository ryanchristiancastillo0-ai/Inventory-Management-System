<?php

declare(strict_types=1);

// ==========================================
// VIEW HELPERS
// ==========================================

require_once __DIR__ . '/../src/Core/helpers.php';

// ==========================================
// IMPORTS
// ==========================================

// Repositories
use App\Repositories\UserRepo;
use App\Repositories\AuthRepo;
use App\Repositories\ProductRepo;
use App\Repositories\CategoryRepo;
use App\Repositories\StockMovementRepo;
use App\Repositories\OrderRepo;
use App\Repositories\OrderItemRepo;
use App\Repositories\OrderHistoryRepo;
use App\Repositories\ActivityLogRepo;
use App\Repositories\ReportRepo;
use App\Repositories\SettingsRepo;
use App\Repositories\FaceRepo;
use App\Repositories\NotificationRepo;
use App\Repositories\PasswordResetRepo;
use App\Repositories\SmsOtpRepo;

// Services
use App\Services\AuthService;
use App\Services\PasskeyService;
use App\Services\SmsService;
use App\Services\SmsAuthService;
use App\Services\ProductService;
use App\Services\ProductImageService;
use App\Services\CategoryService;
use App\Services\StockMovementService;
use App\Services\OrderService;
use App\Services\OrderItemService;
use App\Services\OrderHistoryService;
use App\Services\AdminDashboardService;
use App\Services\AdminUserService;
use App\Services\ActivityLogService;
use App\Services\ReportService;
use App\Services\SettingsService;
use App\Services\ProfileService;
use App\Services\FaceService;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\PasswordResetService;

// Controllers
use App\Controllers\AuthController;
use App\Controllers\PasskeyController;
use App\Controllers\ProductController;
use App\Controllers\CategoryController;
use App\Controllers\StockMovementController;
use App\Controllers\DashboardController;
use App\Controllers\InventoryController;
use App\Controllers\OrderController;
use App\Controllers\OrderHistoryController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AdminUserController;
use App\Controllers\AdminActivityLogController;
use App\Controllers\AdminReportController;
use App\Controllers\PasswordResetController;
use App\Controllers\SettingsController;
use App\Controllers\ProfileController;
use App\Controllers\FaceController;
use App\Controllers\NotificationController;

// Middleware
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;


// ==========================================
// REPOSITORIES
// ==========================================

$userRepo = new UserRepo();
$authRepo = new AuthRepo();
$productRepo = new ProductRepo();
$categoryRepo = new CategoryRepo();
$stockMovementRepo = new StockMovementRepo();
$orderRepo = new OrderRepo();
$orderItemRepo = new OrderItemRepo();
$orderHistoryRepo = new OrderHistoryRepo();
$activityLogRepo = new ActivityLogRepo();
$reportRepo = new ReportRepo();
$settingsRepo = new SettingsRepo();
$notificationRepo = new NotificationRepo();
$passwordResetRepo = new PasswordResetRepo();

$faceRepo = new FaceRepo();
$smsOtpRepo = new SmsOtpRepo();

// ==========================================
// SERVICES
// ==========================================

$emailService = new EmailService();

$notificationService = new NotificationService(
    $notificationRepo,
    $emailService,
    $userRepo,
    $settingsRepo
);

$authService = new AuthService($authRepo);

$passkeyService = new PasskeyService($authRepo);

$smsService = new SmsService();

$smsAuthService = new SmsAuthService($authRepo, $smsOtpRepo, $smsService);

$productService = new ProductService($productRepo);

$categoryService = new CategoryService($categoryRepo);

$stockMovementService = new StockMovementService(
    $stockMovementRepo,
    $notificationService
);

$orderService = new OrderService(
    $orderRepo,
    $notificationService
);

$orderItemService = new OrderItemService(
    $orderItemRepo,
    $orderRepo,
    $productRepo
);

$orderHistoryService = new OrderHistoryService(
    $orderHistoryRepo,
    $orderRepo
);

$adminDashboardService = new AdminDashboardService(
    $userRepo,
    $productRepo,
    $categoryRepo
);


$adminUserService = new AdminUserService(
    $userRepo
);

$activityLogService = new ActivityLogService(
    $activityLogRepo
);


$reportService = new ReportService(
    $reportRepo,
    $userRepo,
    $notificationService
);

$profileService = new ProfileService($userRepo);

$settingsService = new SettingsService(
    $settingsRepo,
    $profileService
);

// Read the saved appearance for every authenticated page, including routes
// protected directly by RoleMiddleware rather than AuthMiddleware.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && $authService->isAuthenticated()) {
    $appTheme = 'SYSTEM';
    try {
        $appSettings = $settingsService->getSettings((int) $_SESSION['user_id']);
        $appTheme = $appSettings['theme'] ?? 'SYSTEM';
    } catch (Throwable $error) {
        error_log((string) $error);
    }
    \App\Core\View::share([
        'appThemePreference' => in_array($appTheme, ['LIGHT', 'DARK', 'SYSTEM'], true) ? $appTheme : 'SYSTEM'
    ]);
}

$faceService = new FaceService(
    $faceRepo,
    $authRepo
);


// ==========================================
// CONTROLLERS
// ==========================================

$authController = new AuthController(
    $authService,
    $smsAuthService
);

$notificationController = new NotificationController(
    $notificationService
);

$passkeyController = new PasskeyController(
    $passkeyService,
    $authService
);

$productImageService = new ProductImageService();

$productController = new ProductController(
    $productService,
    $categoryService,
    $authService,
    $productImageService
);

$categoryController = new CategoryController(
    $categoryService
);

$stockMovementController = new StockMovementController(
    $stockMovementService
);

$dashboardController = new DashboardController(
    $productService,
    $categoryService,
    $stockMovementService,
    $authService
);

$inventoryController = new InventoryController(
    $productService,
    $categoryService,
    $stockMovementService,
    $authService
);

$orderController = new OrderController(
    $orderService,
    $orderItemService,
    $productService,
    $authService,
    $orderHistoryService
);


$orderHistoryController = new OrderHistoryController(
    $orderHistoryService,
    $authService
);

$adminDashboardController = new AdminDashboardController(
    $adminDashboardService
);

$adminUserController = new AdminUserController(
    $adminUserService,
    $authService
);

$adminActivityLogController = new AdminActivityLogController(
    $activityLogService,
    $authService
);
$adminReportController = new AdminReportController(
    $reportService,
    $authService
);

$settingsController = new SettingsController(
    $settingsService,
    $authService,
    $userRepo
);

$passwordResetService = new PasswordResetService(
    $passwordResetRepo,
    $authRepo,
    $notificationService
);

$passwordResetController = new PasswordResetController(
    $passwordResetService,
    $authService
);

$faceController = new FaceController(
    $faceService,
    $authService
);

// ==========================================
// MIDDLEWARE
// ==========================================

$authMiddleware = new AuthMiddleware(
    $authService
);

$profileController = new ProfileController($profileService, $authService, $activityLogService, $smsService, $smsOtpRepo);

$roleMiddleware = new RoleMiddleware(
    $authService
);

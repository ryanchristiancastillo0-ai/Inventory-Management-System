<?php
/** @var string $route */
declare(strict_types=1);

// ==========================================
// INVENTORY ROUTES
// ==========================================

switch ($route) {

    // ======================================
    // USER DASHBOARD
    // ======================================

    case "GET /dashboard":

        $roleMiddleware->handle("USER");

        $dashboardController->index();
        exit;


    // ======================================
    // INVENTORY PAGE
    // ======================================

    case "GET /inventory":

        $roleMiddleware->handle("USER");

        $inventoryController->index();
        exit;


    // ======================================
    // CREATE INVENTORY
    // ======================================

    case "POST /inventory/create":

        $roleMiddleware->handle("USER");

        $inventoryController->store();
        exit;


    // ======================================
    // UPDATE INVENTORY
    // ======================================

    case "POST /inventory/update":

        $roleMiddleware->handle("USER");

        $inventoryController->update();
        exit;


    // ======================================
    // DELETE INVENTORY
    // ======================================

    case "POST /inventory/delete":

        $roleMiddleware->handle("USER");

        $inventoryController->destroy();
        exit;


    // ======================================
    // STOCK IN / STOCK OUT
    // ======================================

    case "POST /inventory/stock":

        $roleMiddleware->handle("USER");

        $inventoryController->recordStock();
        exit;


    // ======================================
    // PRODUCT MANAGEMENT PAGE
    // ======================================

    case "GET /products":

        $roleMiddleware->handle("USER");

        $productController->index();
        exit;


    // ======================================
    // CREATE PRODUCT
    // ======================================

    case "POST /products":

        $roleMiddleware->handle("USER");

        $productController->mutate('create');
        exit;


    // ======================================
    // GET CATEGORIES
    // ======================================

    case "GET /categories":

        $roleMiddleware->handle("USER");

        jsonResponse(
            $categoryController->getAllCategories()
        );


    // ======================================
    // CREATE CATEGORY
    // ======================================

    case "POST /categories":

        $roleMiddleware->handle("USER");

        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !$authService->verifyCsrfToken($token)) {
            jsonResponse(['success' => false, 'message' => 'Reload the page and try again.'], 403);
        }
        $name = $_POST['name'] ?? '';
        if (!is_string($name) || trim($name) === '' || mb_strlen(trim($name)) > 100) {
            jsonResponse(['success' => false, 'message' => 'Enter a category name of up to 100 characters.'], 422);
        }
        try {
            $result = $categoryController->createCategory($name);
            jsonResponse(['success' => $result, 'message' => $result ? 'Category created.' : 'Category name already exists.'], $result ? 201 : 422);
        } catch (\Throwable $error) {
            error_log((string) $error);
            jsonResponse(['success' => false, 'message' => 'Unable to create the category.'], 500);
        }


    // ======================================
    // UPDATE PRODUCT (API for frontend)
    // ======================================

    case "POST /products/update":

        $roleMiddleware->handle("USER");

        $productController->mutate('update');
        exit;


    // ======================================
    // DELETE PRODUCT (API for frontend)
    // ======================================

    case "POST /products/delete":

        $roleMiddleware->handle("USER");

        $productController->mutate('delete');
        exit;


    // ======================================
    // STOCK MOVEMENTS PAGE
    // ======================================

    case "GET /stock-movements":

        $roleMiddleware->handle("USER");

        $stockMovementController->showStockMovements();
        exit;


    // ======================================
    // CREATE STOCK MOVEMENT
    // ======================================

    case "POST /stock-movements":

        $roleMiddleware->handle("USER");

        $result = $stockMovementController
            ->createStockMovement(
                (int) ($_POST["product_id"] ?? 0),
                $_POST["movement_type"] ?? "",
                (int) ($_POST["quantity"] ?? 0),
                $_POST["remarks"] ?? ""
            );

        jsonResponse(
            ["success" => $result],
            $result ? 201 : 400
        );
}

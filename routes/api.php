<?php
/** @var string $route */
declare(strict_types=1);

// ==========================================
// API ROUTES
// ==========================================

switch ($route) {

    // ======================================
    // GET ALL PRODUCTS
    // ======================================

    case "GET /api/products":

        $roleMiddleware->handle("USER");

        jsonResponse(
            $productController->getAllProducts()
        );


    // ======================================
    // GET LOW STOCK PRODUCTS
    // ======================================

    case "GET /api/products/low-stock":

        $roleMiddleware->handle("USER");

        jsonResponse(
            $productController->getLowStockProducts()
        );


    // ======================================
    // GET ALL STOCK MOVEMENTS
    // ======================================

    case "GET /api/stock-movements":

        $roleMiddleware->handle("USER");

        jsonResponse(
            $stockMovementController->getAllStockMovements()
        );


    // ======================================
    // CREATE STOCK MOVEMENT (API)
    // ======================================

    case "POST /api/stock-movements":

        $roleMiddleware->handle("USER");

        $result = $stockMovementController->createStockMovement(
            (int) ($_POST["product_id"] ?? 0),
            $_POST["movement_type"] ?? "",
            (int) ($_POST["quantity"] ?? 0),
            $_POST["remarks"] ?? ""
        );

        jsonResponse(
            ["success" => $result],
            $result ? 201 : 400
        );


    // ======================================
    // GET ALL CATEGORIES
    // ======================================

    case "GET /api/categories":

        $roleMiddleware->handle("USER");

        jsonResponse(
            $categoryController->getAllCategories()
        );


    // ======================================
    // CREATE PRODUCT (API)
    // ======================================

    case "POST /api/products":

        $roleMiddleware->handle("USER");

        $result = $productController->createProduct(
            (int) ($_POST["category_id"] ?? 0),
            $_POST["sku"] ?? "",
            $_POST["name"] ?? "",
            $_POST["description"] ?? "",
            $_POST["price"] ?? "",
            (int) ($_POST["quantity"] ?? 0),
            (int) ($_POST["low_stock_threshold"] ?? 0)
        );

        jsonResponse(
            ["success" => $result],
            $result ? 201 : 400
        );


    // ======================================
    // UPDATE PRODUCT (API)
    // ======================================

    case "POST /api/products/update":

        $roleMiddleware->handle("USER");

        $result = $productController->updateProductById(
            (int) ($_POST["id"] ?? 0),
            (int) ($_POST["category_id"] ?? 0),
            $_POST["sku"] ?? "",
            $_POST["name"] ?? "",
            $_POST["description"] ?? "",
            $_POST["price"] ?? "",
            (int) ($_POST["quantity"] ?? 0),
            (int) ($_POST["low_stock_threshold"] ?? 0)
        );

        jsonResponse(
            ["success" => $result],
            $result ? 200 : 400
        );


    // ======================================
    // DELETE PRODUCT (API)
    // ======================================

    case "POST /api/products/delete":

        $roleMiddleware->handle("USER");

        $result = $productController->deleteProductById(
            (int) ($_POST["id"] ?? 0)
        );

        jsonResponse(
            ["success" => $result],
            $result ? 200 : 400
        );


    // ======================================
    // CREATE CATEGORY (API)
    // ======================================

    case "POST /api/categories":

        $roleMiddleware->handle("USER");

        $result = $categoryController->createCategory(
            $_POST["name"] ?? ""
        );

        jsonResponse(
            ["success" => $result],
            $result ? 201 : 400
        );


    // ======================================
    // UPDATE CATEGORY (API)
    // ======================================

    case "POST /api/categories/update":

        $roleMiddleware->handle("USER");

        $result = $categoryController->updateCategoryById(
            (int) ($_POST["id"] ?? 0),
            $_POST["name"] ?? ""
        );

        jsonResponse(
            ["success" => $result],
            $result ? 200 : 400
        );


    // ======================================
    // DELETE CATEGORY (API)
    // ======================================

    case "POST /api/categories/delete":

        $roleMiddleware->handle("USER");

        $result = $categoryController->deleteCategoryById(
            (int) ($_POST["id"] ?? 0)
        );

        jsonResponse(
            ["success" => $result],
            $result ? 200 : 400
        );


    // ======================================
    // GET ALL ORDERS
    // ======================================

    case "GET /api/orders":

        $roleMiddleware->handle("USER");

        jsonResponse(
            $orderController->getAllOrders()
        );


    // ======================================
    // GET ORDER BY ID
    // ======================================

    case "GET /api/orders/view":

        $roleMiddleware->handle("USER");

        $orderId = (int) ($_GET["id"] ?? 0);
        if ($orderId <= 0) {
            jsonResponse(["error" => "Invalid order ID"], 400);
            break;
        }

        $order = $orderController->findOrderById($orderId);
        if ($order === null) {
            jsonResponse(["error" => "Order not found"], 404);
            break;
        }

        $items = $orderController->getOrderItems($orderId);

        jsonResponse([
            "order" => $order,
            "items" => $items
        ]);


    // ======================================
    // CREATE ORDER (API)
    // ======================================

    case "POST /api/orders":

        $roleMiddleware->handle("USER");

        $userId = (int) ($_SESSION["user_id"] ?? 0);
        $supplierId = (int) ($_POST["supplier_id"] ?? 0);

        if ($userId <= 0 || $supplierId <= 0) {
            jsonResponse(["error" => "Invalid user or supplier"], 400);
            break;
        }

        $number = 'PO-' . strtoupper(bin2hex(random_bytes(8)));
        $orderId = $orderController->createOrder($userId, $number, '0.00', $supplierId);

        if ($orderId === null) {
            jsonResponse(["error" => "Unable to create order"], 400);
            break;
        }

        jsonResponse(
            ["success" => true, "order_id" => $orderId, "order_number" => $number],
            201
        );


    // ======================================
    // ADD ORDER ITEM (API)
    // ======================================

    case "POST /api/orders/items/add":

        $roleMiddleware->handle("USER");

        $orderId = (int) ($_POST["order_id"] ?? 0);
        $productId = (int) ($_POST["product_id"] ?? 0);
        $quantity = (int) ($_POST["quantity"] ?? 0);
        $price = trim((string) ($_POST["unit_price"] ?? ''));

        if ($orderId <= 0 || $productId <= 0 || $quantity <= 0 || $price === '') {
            jsonResponse(["error" => "Invalid item data"], 400);
            break;
        }

        $result = $orderController->addOrderItem($orderId, $productId, $quantity, $price);

        jsonResponse(
            ["success" => $result],
            $result ? 201 : 400
        );


    // ======================================
    // UPDATE ORDER ITEM (API)
    // ======================================

    case "POST /api/orders/items/update":

        $roleMiddleware->handle("USER");

        $itemId = (int) ($_POST["item_id"] ?? 0);
        $quantity = (int) ($_POST["quantity"] ?? 0);
        $price = trim((string) ($_POST["unit_price"] ?? ''));

        if ($itemId <= 0 || $quantity <= 0 || $price === '') {
            jsonResponse(["error" => "Invalid item data"], 400);
            break;
        }

        $result = $orderController->updateOrderItem($itemId, $quantity, $price);

        jsonResponse(
            ["success" => $result],
            $result ? 200 : 400
        );


    // ======================================
    // DELETE ORDER ITEM (API)
    // ======================================

    case "POST /api/orders/items/delete":

        $roleMiddleware->handle("USER");

        $itemId = (int) ($_POST["item_id"] ?? 0);

        if ($itemId <= 0) {
            jsonResponse(["error" => "Invalid item ID"], 400);
            break;
        }

        $result = $orderController->deleteOrderItem($itemId);

        jsonResponse(
            ["success" => $result],
            $result ? 200 : 400
        );


    // ======================================
    // SUBMIT ORDER (API)
    // ======================================

    case "POST /api/orders/submit":

        $roleMiddleware->handle("USER");

        $orderId = (int) ($_POST["order_id"] ?? 0);

        if ($orderId <= 0) {
            jsonResponse(["error" => "Invalid order ID"], 400);
            break;
        }

        $result = $orderController->markAsOrdered($orderId);

        jsonResponse(
            ["success" => $result],
            $result ? 200 : 400
        );


    // ======================================
    // CANCEL ORDER (API)
    // ======================================

    case "POST /api/orders/cancel":

        $roleMiddleware->handle("USER");

        $orderId = (int) ($_POST["order_id"] ?? 0);

        if ($orderId <= 0) {
            jsonResponse(["error" => "Invalid order ID"], 400);
            break;
        }

        $result = $orderController->updateOrderStatus($orderId, 'CANCELLED');

        jsonResponse(
            ["success" => $result],
            $result ? 200 : 400
        );


    // ======================================
    // GET ORDER HISTORY
    // ======================================

    case "GET /api/order-history":

        $roleMiddleware->handle("USER");

        jsonResponse(
            $orderHistoryController->getAllOrderHistory()
        );
}

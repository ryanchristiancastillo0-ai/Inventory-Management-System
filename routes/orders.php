<?php
/** @var string $route */
declare(strict_types=1);

// ==========================================
// ORDER MANAGEMENT ROUTES
// ==========================================

switch ($route) {

    // ======================================
    // ORDERS PAGE
    // ======================================

    case "GET /orders":

        $roleMiddleware->handle("USER");

        $orderController->index();
        exit;


    // ======================================
    // CREATE ORDER
    // ======================================

    case "POST /orders/create":

        $roleMiddleware->handle("USER");

        $orderController->store();
        exit;


    // ======================================
    // CANCEL ORDER
    // ======================================

    case "POST /orders/cancel":

        $roleMiddleware->handle("USER");

        $orderController->cancel();
        exit;


    // ======================================
    // DELETE PENDING ORDER
    // ======================================

    case "POST /orders/delete":

        $roleMiddleware->handle("USER");

        $orderController->destroy();
        exit;


    // ======================================
    // SUBMIT RESTOCK ORDER
    // ======================================

    case "POST /orders/submit":

        $roleMiddleware->handle("USER");

        $orderController->submit();
        exit;


    // ======================================
    // ADD ORDER ITEM
    // ======================================

    case "POST /orders/items/add":

        $roleMiddleware->handle("USER");

        $orderController->addItem();
        exit;


    // ======================================
    // UPDATE ORDER ITEM
    // ======================================

    case "POST /orders/items/update":

        $roleMiddleware->handle("USER");

        $orderController->updateItem();
        exit;


    // ======================================
    // DELETE ORDER ITEM
    // ======================================

    case "POST /orders/items/delete":

        $roleMiddleware->handle("USER");

        $orderController->removeItem();
        exit;


    // ======================================
    // SUPPLIER MANAGEMENT
    // (need at least one supplier before a
    //  restock order can be created — fk_orders_supplier)
    // ======================================

    case "POST /suppliers/create":

        $roleMiddleware->handle("USER");

        $supplierController->store();
        exit;


    case "POST /suppliers/update":

        $roleMiddleware->handle("USER");

        $supplierController->update();
        exit;


    case "POST /suppliers/delete":

        $roleMiddleware->handle("USER");

        $supplierController->destroy();
        exit;


    // ======================================
    // ORDER HISTORY PAGE
    // ======================================

    case "GET /order-history":

        $roleMiddleware->handle("USER");

        $orderHistoryController->index();
        exit;


    // ======================================
    // ORDER HISTORY DETAILS
    // ======================================

    case "GET /order-history/view":

        $roleMiddleware->handle("USER");

        $orderHistoryController->show();
        exit;
}

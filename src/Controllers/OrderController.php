<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AuthService;
use App\Services\OrderHistoryService;
use App\Services\OrderItemService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\SupplierService;
use RuntimeException;
use Throwable;

class OrderController
{
    public function __construct(
        private OrderService $orderService,
        private OrderItemService $orderItemService,
        private ProductService $productService,
        private AuthService $authService,
        private OrderHistoryService $orderHistoryService,
        private SupplierService $supplierService
    ) {}

    // GET /orders — Restocking orders page
    public function index(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $orders = $this->orderService->getAllOrders($userId);
        $products = $this->productService->getAllProducts($userId);
        $suppliers = $this->supplierService->getAllSuppliers();

        $pendingOrders = 0;
        $orderedOrders = 0;
        $receivedOrders = 0;
        $cancelledOrders = 0;
        $totalRestockCost = 0.0;

        foreach ($orders as $order) {
            switch ($order['status']) {
                case 'PENDING':
                    $pendingOrders++;
                    break;
                case 'ORDERED':
                    $orderedOrders++;
                    break;
                case 'RECEIVED':
                    $receivedOrders++;
                    $totalRestockCost += (float) $order['total_amount'];
                    break;
                case 'CANCELLED':
                    $cancelledOrders++;
                    break;
            }
        }

        $selectedOrder = null;
        $orderItems = [];
        $orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (is_int($orderId) && $orderId > 0) {
            $selectedOrder = $this->orderService->findOrderById($orderId);
            if ($selectedOrder !== null) {
                $orderItems = $this->orderItemService->getItemsByOrderId($orderId);
            }
        }

        View::render('user/orders/index', [
            'title' => 'Restock Orders',
            'orders' => $orders,
            'products' => $products,
            'suppliers' => $suppliers,
            'totalOrders' => count($orders),
            'pendingOrders' => $pendingOrders,
            'orderedOrders' => $orderedOrders,
            'receivedOrders' => $receivedOrders,
            'cancelledOrders' => $cancelledOrders,
            'totalRestockCost' => $totalRestockCost,
            'selectedOrder' => $selectedOrder,
            'orderItems' => $orderItems,
            'csrfToken' => $this->authService->getCsrfToken(),
        ]);
    }

    // ==========================================
    // API: GET ALL ORDERS
    // ==========================================

    public function getAllOrders(): array
    {
        return $this->orderService->getAllOrders();
    }

    // ==========================================
    // API: FIND ORDER BY ID
    // ==========================================

    public function findOrderById(int $id): ?array
    {
        return $this->orderService->findOrderById($id);
    }

    // ==========================================
    // API: CREATE ORDER
    // ==========================================

    public function createOrder(int $userId, string $orderNumber, string $totalAmount, ?int $supplierId): ?int
    {
        return $this->orderService->createOrder($userId, $orderNumber, $totalAmount, $supplierId);
    }

    // ==========================================
    // API: MARK AS ORDERED
    // ==========================================

    public function markAsOrdered(int $orderId): bool
    {
        return $this->orderService->markAsOrdered($orderId);
    }

    // ==========================================
    // API: UPDATE ORDER STATUS
    // ==========================================

    public function updateOrderStatus(int $id, string $status): bool
    {
        return $this->orderService->updateOrderStatus($id, $status);
    }

    // ==========================================
    // API: ADD ORDER ITEM
    // ==========================================

    public function addOrderItem(int $orderId, int $productId, int $quantity, string $unitPrice): bool
    {
        return $this->orderItemService->createOrderItem($orderId, $productId, $quantity, $unitPrice);
    }

    // ==========================================
    // API: UPDATE ORDER ITEM
    // ==========================================

    public function updateOrderItem(int $itemId, int $quantity, string $unitPrice): bool
    {
        return $this->orderItemService->updateOrderItemById($itemId, $quantity, $unitPrice);
    }

    // ==========================================
    // API: DELETE ORDER ITEM
    // ==========================================

    public function deleteOrderItem(int $itemId): bool
    {
        return $this->orderItemService->deleteOrderItemById($itemId);
    }

    // ==========================================
    // API: GET ORDER ITEMS
    // ==========================================

    public function getOrderItems(int $orderId): array
    {
        return $this->orderItemService->getItemsByOrderId($orderId);
    }

    private function requireValidRequest(): void
    {
        $token = $_POST['csrf_token'] ?? null;
        if (!is_string($token) || !$this->authService->verifyCsrfToken($token)) {
            http_response_code(403);
            exit('Invalid security token.');
        }
        if (!$this->authService->isAuthenticated()) {
            http_response_code(401);
            exit('Authentication required.');
        }
    }

    private function currentUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private function postId(string $field): ?int
    {
        $value = filter_var($_POST[$field] ?? null, FILTER_VALIDATE_INT);
        return is_int($value) && $value > 0 ? $value : null;
    }

    private function validPurchasePrice(string $value): bool
    {
        return preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $value) === 1;
    }

    private function redirect(string $message, bool $success, ?int $orderId = null): never
    {
        $_SESSION[$success ? 'success' : 'error'] = $message;
        $url = '/orders';
        if ($orderId !== null && $orderId > 0) {
            $url .= '?id=' . $orderId;
        }
        header('Location: ' . $url, true, 303);
        exit;
    }

    // This is not atomic with order edits yet. A coordinated transaction
    // should move history writing into an order workflow service later.
    private function recordHistory(
        int $orderId,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        string $remarks
    ): bool {
        try {
            $saved = $this->orderHistoryService->recordOrderHistory(
                $orderId,
                $this->currentUserId(),
                $action,
                $oldStatus,
                $newStatus,
                $remarks
            );
            if (!$saved) {
                error_log("Order history not saved for order {$orderId}: {$action}");
            }
            return $saved;
        } catch (Throwable $e) {
            error_log('Order history error: ' . $e->getMessage());
            return false;
        }
    }

    private function syncOrderTotal(int $orderId): void
    {
        $total = $this->orderItemService->getOrderTotal($orderId);
        if (!$this->orderService->updateOrderTotal($orderId, $total)) {
            throw new RuntimeException('Unable to synchronize order total.');
        }
    }

    // POST /orders/create — Create pending supplier restock order
    public function store(): void
    {
        $this->requireValidRequest();
        $userId = $this->currentUserId();
        $supplierId = $this->postId('supplier_id');

        if ($userId <= 0 || $supplierId === null) {
            $this->redirect('Please select a valid supplier.', false);
        }

        try {
            $number = 'PO-' . strtoupper(bin2hex(random_bytes(8)));
            $orderId = $this->orderService->createOrder(
                $userId, $number, '0.00', $supplierId
            );
            if ($orderId === null) {
                $this->redirect('Unable to create restock order.', false);
            }
            $logged = $this->recordHistory(
                $orderId, 'CREATED', null, 'PENDING', 'Restock order created.'
            );
            $this->redirect(
                $logged ? 'Restock order created.' : 'Order created, but history could not be recorded.',
                $logged, $orderId
            );
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->redirect('Database error while creating restock order.', false);
        }
    }

    // POST /orders/items/add — Purchase price is NOT the selling price
    public function addItem(): void
    {
        $this->requireValidRequest();
        $orderId = $this->postId('order_id');
        $productId = $this->postId('product_id');
        $quantity = $this->postId('quantity');
        $price = trim((string) ($_POST['unit_price'] ?? ''));

        if ($orderId === null || $productId === null || $quantity === null ||
            !$this->validPurchasePrice($price)) {
            $this->redirect('Invalid product, quantity, or supplier purchase price.', false, $orderId);
        }

        try {
            $product = $this->productService->findProductById($productId);
            if ($product === null) {
                $this->redirect('Product not found.', false, $orderId);
            }
            if (!$this->orderItemService->createOrderItem($orderId, $productId, $quantity, $price)) {
                $this->redirect('Cannot add item. Order must be pending and item not duplicated.', false, $orderId);
            }
            $this->syncOrderTotal($orderId);
            $logged = $this->recordHistory(
                $orderId, 'UPDATED', 'PENDING', 'PENDING', 'Restock item added.'
            );
            $this->redirect(
                $logged ? 'Product added to restock order.' : 'Item added, but history could not be recorded.',
                $logged, $orderId
            );
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->redirect('Unable to add item or synchronize total.', false, $orderId);
        }
    }

    // POST /orders/items/update
    public function updateItem(): void
    {
        $this->requireValidRequest();
        $itemId = $this->postId('item_id');
        $quantity = $this->postId('quantity');
        if ($itemId === null || $quantity === null) {
            $this->redirect('Invalid item or quantity.', false);
        }

        try {
            $item = $this->orderItemService->findOrderItemById($itemId);
            if ($item === null) {
                $this->redirect('Order item not found.', false);
            }
            $orderId = (int) $item['order_id'];
            $price = trim((string) ($_POST['unit_price'] ?? $item['unit_price']));
            if (!$this->validPurchasePrice($price)) {
                $this->redirect('Invalid supplier purchase price.', false, $orderId);
            }
            if (!$this->orderItemService->updateOrderItemById($itemId, $quantity, $price)) {
                $this->redirect('Cannot update item. Order must be pending.', false, $orderId);
            }
            $this->syncOrderTotal($orderId);
            $logged = $this->recordHistory(
                $orderId, 'UPDATED', 'PENDING', 'PENDING', 'Restock item updated.'
            );
            $this->redirect(
                $logged ? 'Restock item updated.' : 'Item updated, but history could not be recorded.',
                $logged, $orderId
            );
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->redirect('Unable to update restock item.', false);
        }
    }

    // POST /orders/items/delete
    public function removeItem(): void
    {
        $this->requireValidRequest();
        $itemId = $this->postId('item_id');
        if ($itemId === null) {
            $this->redirect('Invalid item ID.', false);
        }
        try {
            $item = $this->orderItemService->findOrderItemById($itemId);
            if ($item === null) {
                $this->redirect('Order item not found.', false);
            }
            $orderId = (int) $item['order_id'];
            if (!$this->orderItemService->deleteOrderItemById($itemId)) {
                $this->redirect('Cannot remove item. Order must be pending.', false, $orderId);
            }
            $this->syncOrderTotal($orderId);
            $logged = $this->recordHistory(
                $orderId, 'UPDATED', 'PENDING', 'PENDING', 'Restock item removed.'
            );
            $this->redirect(
                $logged ? 'Restock item removed.' : 'Item removed, but history could not be recorded.',
                $logged, $orderId
            );
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->redirect('Unable to remove restock item.', false);
        }
    }

    // POST /orders/submit — PENDING -> ORDERED (no stock changes)
    public function submit(): void
    {
        $this->requireValidRequest();
        $orderId = $this->postId('order_id');
        if ($orderId === null) {
            $this->redirect('Invalid order ID.', false);
        }
        try {
            if ($this->orderItemService->getItemsByOrderId($orderId) === []) {
                $this->redirect('Add products before placing the order.', false, $orderId);
            }
            if (!$this->orderService->markAsOrdered($orderId)) {
                $this->redirect('Order must be pending and have a supplier.', false, $orderId);
            }
            $logged = $this->recordHistory(
                $orderId, 'ORDERED', 'PENDING', 'ORDERED', 'Restock order placed with supplier.'
            );
            $this->redirect(
                $logged ? 'Restock order placed with supplier.' : 'Order placed, but history could not be recorded.',
                $logged, $orderId
            );
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->redirect('Unable to place restock order.', false, $orderId);
        }
    }

    // POST /orders/cancel — PENDING/ORDERED -> CANCELLED
    public function cancel(): void
    {
        $this->requireValidRequest();
        $orderId = $this->postId('order_id');
        if ($orderId === null) {
            $this->redirect('Invalid order ID.', false);
        }
        try {
            $order = $this->orderService->findOrderById($orderId);
            if ($order === null) {
                $this->redirect('Order not found.', false, $orderId);
            }
            $oldStatus = (string) $order['status'];
            if (!$this->orderService->updateOrderStatus($orderId, 'CANCELLED')) {
                $this->redirect('Only pending or ordered restocks can be cancelled.', false, $orderId);
            }
            $logged = $this->recordHistory(
                $orderId, 'CANCELLED', $oldStatus, 'CANCELLED', 'Restock order cancelled.'
            );
            $this->redirect(
                $logged ? 'Restock order cancelled.' : 'Order cancelled, but history could not be recorded.',
                $logged, $orderId
            );
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->redirect('Unable to cancel order.', false, $orderId);
        }
    }

    // POST /orders/delete — only pending orders; note ON DELETE CASCADE
    // also removes linked history with the current schema.
    public function destroy(): void
    {
        $this->requireValidRequest();
        $orderId = $this->postId('order_id');
        if ($orderId === null) {
            $this->redirect('Invalid order ID.', false);
        }
        try {
            $this->redirect(
                'Order deletion is disabled to preserve history. Cancel the order instead.',
                false, $orderId
            );
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->redirect('Unable to delete order.', false, $orderId);
        }
    }
}

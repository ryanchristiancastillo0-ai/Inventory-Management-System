<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepo;

class OrderService
{
    private OrderRepo $orderRepo;

    private ?NotificationService $notificationService;

    private const STATUSES = [
        'PENDING',
        'ORDERED',
        'RECEIVED',
        'CANCELLED',
        'COMPLETED'
    ];

    public function __construct(
        OrderRepo $orderRepo,
        ?NotificationService $notificationService = null
    ) {
        $this->orderRepo = $orderRepo;
        $this->notificationService = $notificationService;
    }

    // ==========================================
    // GET ALL ORDERS
    // ==========================================

    public function getAllOrders(): array
    {
        return $this->orderRepo->getAllOrders();
    }

    // ==========================================
    // FIND ORDER BY ID
    // ==========================================

    public function findOrderById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $order = $this->orderRepo->getOrderById($id);

        return $order === false ? null : $order;
    }

    // ==========================================
    // FIND ORDER BY ORDER NUMBER
    // ==========================================

    public function findOrderByNumber(
        string $orderNumber
    ): ?array {
        $orderNumber = trim($orderNumber);

        if ($orderNumber === '') {
            return null;
        }

        $order = $this->orderRepo
            ->findOrderByNumber($orderNumber);

        return $order === false ? null : $order;
    }

    // ==========================================
    // GET ORDERS BY USER
    // ==========================================

    public function getOrdersByUserId(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [];
        }

        return $this->orderRepo
            ->getOrdersByUserId($userId);
    }

    // ==========================================
    // GET ORDERS BY SUPPLIER
    // ==========================================

    public function getOrdersBySupplierId(
        int $supplierId
    ): array {
        if ($supplierId <= 0) {
            return [];
        }

        return $this->orderRepo
            ->getOrdersBySupplierId($supplierId);
    }

    // ==========================================
    // GET ORDERS BY STATUS
    // ==========================================

    public function getOrdersByStatus(
        string $status
    ): array {
        $status = strtoupper(trim($status));

        if (!in_array(
            $status,
            self::STATUSES,
            true
        )) {
            return [];
        }

        return $this->orderRepo
            ->getOrdersByStatus($status);
    }

    // ==========================================
    // CREATE RESTOCK ORDER
    // RETURNS ORDER ID OR NULL
    // ==========================================

    public function createOrder(
        int $userId,
        string $orderNumber,
        string $totalAmount = '0.00',
        ?int $supplierId = null
    ): ?int {
        $orderNumber = trim($orderNumber);

        if (
            $userId <= 0 ||
            $orderNumber === '' ||
            strlen($orderNumber) > 50 ||
            ($supplierId !== null && $supplierId <= 0) ||
            !$this->isValidAmount($totalAmount)
        ) {
            return null;
        }

        // CHECK DUPLICATE ORDER NUMBER
        $existingOrder = $this->orderRepo
            ->findOrderByNumber($orderNumber);

        if ($existingOrder !== false) {
            return null;
        }

        // CREATE PENDING RESTOCK ORDER
        $orderId = $this->orderRepo->createOrder(
            $userId,
            $orderNumber,
            $totalAmount,
            $supplierId
        );

        return $orderId > 0 ? $orderId : null;
    }

    // ==========================================
    // UPDATE ORDER SUPPLIER
    // ONLY PENDING ORDERS
    // ==========================================

    public function updateOrderSupplier(
        int $orderId,
        int $supplierId
    ): bool {
        if (
            $orderId <= 0 ||
            $supplierId <= 0
        ) {
            return false;
        }

        $order = $this->orderRepo
            ->getOrderById($orderId);

        if (
            $order === false ||
            $order['status'] !== 'PENDING'
        ) {
            return false;
        }

        return $this->orderRepo
            ->updateOrderSupplier(
                $orderId,
                $supplierId
            );
    }

    // ==========================================
    // MARK RESTOCK ORDER AS ORDERED
    // PENDING -> ORDERED
    // ==========================================

    public function markAsOrdered(int $orderId): bool
    {
        if ($orderId <= 0) {
            return false;
        }

        $order = $this->orderRepo
            ->getOrderById($orderId);

        if (
            $order === false ||
            $order['status'] !== 'PENDING' ||
            empty($order['supplier_id'])
        ) {
            return false;
        }

        $updated = $this->orderRepo
            ->markAsOrdered($orderId);

        if ($updated) {
            $this->notifyOrderStatus(
                $order,
                'ORDERED'
            );
        }

        return $updated;
    }

    // ==========================================
    // UPDATE ORDER STATUS
    // COMPATIBILITY WITH EXISTING CONTROLLER
    // ==========================================

    public function updateOrderStatus(
        int $id,
        string $status
    ): bool {
        $status = strtoupper(trim($status));

        if (
            $id <= 0 ||
            !in_array(
                $status,
                self::STATUSES,
                true
            )
        ) {
            return false;
        }

        $order = $this->orderRepo
            ->getOrderById($id);

        if ($order === false) {
            return false;
        }

        $currentStatus = strtoupper(
            (string) $order['status']
        );

        // ALREADY FINALIZED
        if (in_array(
            $currentStatus,
            ['RECEIVED', 'CANCELLED', 'COMPLETED'],
            true
        )) {
            return false;
        }

        // AVOID REPEATING SAME STATUS
        if ($currentStatus === $status) {
            return false;
        }

        // RECEIVING MUST UPDATE STOCK FIRST
        // THROUGH A DATABASE TRANSACTION
        if (in_array(
            $status,
            ['RECEIVED', 'COMPLETED'],
            true
        )) {
            return false;
        }

        // PENDING -> ORDERED
        if (
            $currentStatus === 'PENDING' &&
            $status === 'ORDERED'
        ) {
            return $this->markAsOrdered($id);
        }

        // PENDING / ORDERED -> CANCELLED
        if (
            in_array(
                $currentStatus,
                ['PENDING', 'ORDERED'],
                true
            ) &&
            $status === 'CANCELLED'
        ) {
            $cancelled = $this->orderRepo
                ->cancelOrder($id);

            if ($cancelled) {
                $this->notifyOrderStatus(
                    $order,
                    'CANCELLED'
                );
            }

            return $cancelled;
        }

        return false;
    }

    // ==========================================
    // EMAIL NOTIFICATIONS (BREVO)
    // Fired after a successful status change; failures are
    // swallowed by NotificationService.
    // ==========================================

    private function notifyOrderStatus(
        array $order,
        string $newStatus
    ): void {
        $ownerId = (int) ($order['user_id'] ?? 0);

        if ($ownerId <= 0 || $this->notificationService === null) {
            return;
        }

        $orderNumber = (string) ($order['order_number'] ?? ('#' . ($order['id'] ?? '')));

        $labels = [
            'ORDERED' => 'Order placed with supplier',
            'CANCELLED' => 'Order cancelled'
        ];

        $statusLabel = $labels[$newStatus] ?? $newStatus;

        $this->notificationService->send(
            $ownerId,
            NotificationService::TYPE_ORDER,
            "Order {$orderNumber} - " . ucfirst(strtolower($statusLabel)),
            $statusLabel,
            [
                "Order {$orderNumber} status updated to {$newStatus}.",
                "You can follow the order in your order history.",
            ],
            [],
            [
                "label" => "Open order history",
                "url" => self::orderBaseUrl() . "/orders/history"
            ]
        );
    }

    private static function orderBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }

    // ==========================================
    // UPDATE ORDER TOTAL
    // ONLY PENDING ORDERS
    // ==========================================

    public function updateOrderTotal(
        int $id,
        string $totalAmount
    ): bool {
        if (
            $id <= 0 ||
            !$this->isValidAmount($totalAmount)
        ) {
            return false;
        }

        $order = $this->orderRepo
            ->getOrderById($id);

        if (
            $order === false ||
            $order['status'] !== 'PENDING'
        ) {
            return false;
        }

        return $this->orderRepo
            ->updateOrderTotal(
                $id,
                $totalAmount
            );
    }

    // ==========================================
    // DELETE PENDING ORDER
    // ==========================================

    public function deleteOrderById(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $order = $this->orderRepo
            ->getOrderById($id);

        if (
            $order === false ||
            $order['status'] !== 'PENDING'
        ) {
            return false;
        }

        return $this->orderRepo
            ->deleteOrderById($id);
    }

    // ==========================================
    // COUNT ORDERS BY STATUS
    // ==========================================

    public function countOrdersByStatus(
        string $status
    ): int {
        $status = strtoupper(trim($status));

        if (!in_array(
            $status,
            self::STATUSES,
            true
        )) {
            return 0;
        }

        return $this->orderRepo
            ->countOrdersByStatus($status);
    }

    // ==========================================
    // VALIDATE DECIMAL AMOUNT
    // ==========================================

    private function isValidAmount(
        string $amount
    ): bool {
        return (bool) preg_match(
            '/^\d{1,10}(\.\d{1,2})?$/',
            trim($amount)
        );
    }
}

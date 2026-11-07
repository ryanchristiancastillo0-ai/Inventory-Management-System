<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderItemRepo;
use App\Repositories\OrderRepo;
use App\Repositories\ProductRepo;

class OrderItemService
{
    private OrderItemRepo $orderItemRepo;
    private OrderRepo $orderRepo;
    private ProductRepo $productRepo;

    public function __construct(
        OrderItemRepo $orderItemRepo,
        OrderRepo $orderRepo,
        ProductRepo $productRepo
    ) {
        $this->orderItemRepo = $orderItemRepo;
        $this->orderRepo = $orderRepo;
        $this->productRepo = $productRepo;
    }

    // ==========================================
    // GET ALL ORDER ITEMS
    // ==========================================

    public function getAllOrderItems(): array
    {
        return $this->orderItemRepo->getAllOrderItems();
    }

    // ==========================================
    // FIND ORDER ITEM BY ID
    // ==========================================

    public function findOrderItemById(
        int $id
    ): ?array {
        if ($id <= 0) {
            return null;
        }

        $item = $this->orderItemRepo
            ->getOrderItemById($id);

        return $item === false ? null : $item;
    }

    // ==========================================
    // GET ITEMS BY ORDER ID
    // ==========================================

    public function getItemsByOrderId(
        int $orderId
    ): array {
        if ($orderId <= 0) {
            return [];
        }

        return $this->orderItemRepo
            ->getItemsByOrderId($orderId);
    }

    // ==========================================
    // FIND ITEM BY ORDER AND PRODUCT
    // ==========================================

    public function findItemByOrderAndProduct(
        int $orderId,
        int $productId
    ): ?array {
        if ($orderId <= 0 || $productId <= 0) {
            return null;
        }

        $item = $this->orderItemRepo
            ->findItemByOrderAndProduct(
                $orderId,
                $productId
            );

        return $item === false ? null : $item;
    }

    // ==========================================
    // ADD PRODUCT TO ORDER
    // ==========================================

    public function createOrderItem(
        int $orderId,
        int $productId,
        int $quantity,
        string $unitPrice
    ): bool {
        // VALIDATE INPUT
        if (
            $orderId <= 0 ||
            $productId <= 0 ||
            $quantity <= 0 ||
            !is_numeric($unitPrice) ||
            !is_finite((float) $unitPrice) ||
            (float) $unitPrice < 0
        ) {
            return false;
        }

        // CHECK IF ORDER EXISTS
        $order = $this->orderRepo
            ->getOrderById($orderId);

        if (
            $order === false ||
            $order['status'] !== 'PENDING'
        ) {
            return false;
        }

        // CHECK IF PRODUCT EXISTS
        $product = $this->productRepo
            ->getProductById($productId);

        if ($product === false) {
            return false;
        }

        // CHECK DUPLICATE ITEM
        $existingItem = $this->orderItemRepo
            ->findItemByOrderAndProduct(
                $orderId,
                $productId
            );

        if ($existingItem !== false) {
            return false;
        }

        // SAVE ORDER ITEM
        return $this->orderItemRepo
            ->createOrderItem(
                $orderId,
                $productId,
                $quantity,
                $unitPrice
            );
    }

    // ==========================================
    // UPDATE ORDER ITEM
    // ==========================================

    public function updateOrderItemById(
        int $id,
        int $quantity,
        string $unitPrice
    ): bool {
        if (
            $id <= 0 ||
            $quantity <= 0 ||
            !is_numeric($unitPrice) ||
            !is_finite((float) $unitPrice) ||
            (float) $unitPrice < 0
        ) {
            return false;
        }

        // CHECK IF ITEM EXISTS
        $item = $this->orderItemRepo
            ->getOrderItemById($id);

        if ($item === false) {
            return false;
        }

        // CHECK ORDER STATUS
        $order = $this->orderRepo
            ->getOrderById((int) $item['order_id']);

        if (
            $order === false ||
            $order['status'] !== 'PENDING'
        ) {
            return false;
        }

        return $this->orderItemRepo
            ->updateOrderItemById(
                $id,
                $quantity,
                $unitPrice
            );
    }

    // ==========================================
    // GET ORDER TOTAL
    // ==========================================

    public function getOrderTotal(
        int $orderId
    ): string {
        if ($orderId <= 0) {
            return '0.00';
        }

        return $this->orderItemRepo
            ->getOrderTotal($orderId);
    }

    // ==========================================
    // DELETE ORDER ITEM
    // ==========================================

    public function deleteOrderItemById(
        int $id
    ): bool {
        if ($id <= 0) {
            return false;
        }

        // CHECK IF ITEM EXISTS
        $item = $this->orderItemRepo
            ->getOrderItemById($id);

        if ($item === false) {
            return false;
        }

        // CHECK ORDER STATUS
        $order = $this->orderRepo
            ->getOrderById((int) $item['order_id']);

        if (
            $order === false ||
            $order['status'] !== 'PENDING'
        ) {
            return false;
        }

        return $this->orderItemRepo
            ->deleteOrderItemById($id);
    }

    // ==========================================
    // DELETE ALL ITEMS IN AN ORDER
    // ==========================================

    public function deleteItemsByOrderId(
        int $orderId
    ): bool {
        if ($orderId <= 0) {
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

        return $this->orderItemRepo
            ->deleteItemsByOrderId($orderId);
    }
}

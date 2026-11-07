<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderHistoryRepo;
use App\Repositories\OrderRepo;

class OrderHistoryService
{
    private OrderHistoryRepo $orderHistoryRepo;
    private OrderRepo $orderRepo;

    // ==========================================
    // ALLOWED HISTORY ACTIONS
    // ==========================================

    private const ACTIONS = [
        'CREATED',
        'UPDATED',
        'ORDERED',
        'RECEIVED',
        'COMPLETED',
        'CANCELLED'
    ];

    // ==========================================
    // ALLOWED ORDER STATUSES
    // ==========================================

    private const STATUSES = [
        'PENDING',
        'ORDERED',
        'RECEIVED',
        'COMPLETED',
        'CANCELLED'
    ];

    public function __construct(
        OrderHistoryRepo $orderHistoryRepo,
        OrderRepo $orderRepo
    ) {
        $this->orderHistoryRepo = $orderHistoryRepo;
        $this->orderRepo = $orderRepo;
    }

    // ==========================================
    // GET ALL ORDER HISTORY
    // ==========================================

    public function getAllOrderHistory(): array
    {
        return $this->orderHistoryRepo
            ->getAllOrderHistory();
    }

    // ==========================================
    // FIND HISTORY BY ID
    // ==========================================

    public function findOrderHistoryById(
        int $id
    ): ?array {
        if ($id <= 0) {
            return null;
        }

        $history = $this->orderHistoryRepo
            ->getOrderHistoryById($id);

        return $history === false ? null : $history;
    }

    // ==========================================
    // GET HISTORY BY ORDER ID
    // ==========================================

    public function getHistoryByOrderId(
        int $orderId
    ): array {
        if ($orderId <= 0) {
            return [];
        }

        return $this->orderHistoryRepo
            ->getHistoryByOrderId($orderId);
    }

    // ==========================================
    // GET HISTORY BY USER ID
    // ==========================================

    public function getHistoryByUserId(
        int $userId
    ): array {
        if ($userId <= 0) {
            return [];
        }

        return $this->orderHistoryRepo
            ->getHistoryByUserId($userId);
    }

    // ==========================================
    // GET HISTORY BY ACTION
    // ==========================================

    public function getHistoryByAction(
        string $action
    ): array {
        $action = strtoupper(trim($action));

        if (!in_array(
            $action,
            self::ACTIONS,
            true
        )) {
            return [];
        }

        return $this->orderHistoryRepo
            ->getHistoryByAction($action);
    }

    // ==========================================
    // RECORD ORDER HISTORY
    // ==========================================

    public function recordOrderHistory(
        int $orderId,
        ?int $userId,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?string $remarks = null
    ): bool {
        $action = strtoupper(trim($action));

        $oldStatus = $oldStatus === null
            ? null
            : strtoupper(trim($oldStatus));

        $newStatus = $newStatus === null
            ? null
            : strtoupper(trim($newStatus));

        $remarks = $remarks === null
            ? null
            : trim($remarks);

        // VALIDATE ORDER ID
        if ($orderId <= 0) {
            return false;
        }

        // VALIDATE USER ID
        if (
            $userId !== null &&
            $userId <= 0
        ) {
            return false;
        }

        // VALIDATE ACTION
        if (!in_array(
            $action,
            self::ACTIONS,
            true
        )) {
            return false;
        }

        // VALIDATE OLD STATUS
        if (
            $oldStatus !== null &&
            !in_array(
                $oldStatus,
                self::STATUSES,
                true
            )
        ) {
            return false;
        }

        // VALIDATE NEW STATUS
        if (
            $newStatus !== null &&
            !in_array(
                $newStatus,
                self::STATUSES,
                true
            )
        ) {
            return false;
        }

        // VALIDATE ACTION AND STATUS RELATIONSHIP
        if (
            $action === 'CREATED' &&
            (
                $oldStatus !== null ||
                $newStatus !== 'PENDING'
            )
        ) {
            return false;
        }

        if (
            $action === 'ORDERED' &&
            (
                $oldStatus !== 'PENDING' ||
                $newStatus !== 'ORDERED'
            )
        ) {
            return false;
        }

        if (
            $action === 'RECEIVED' &&
            (
                $oldStatus !== 'ORDERED' ||
                $newStatus !== 'RECEIVED'
            )
        ) {
            return false;
        }

        if (
            $action === 'CANCELLED' &&
            (
                !in_array(
                    $oldStatus,
                    ['PENDING', 'ORDERED'],
                    true
                ) ||
                $newStatus !== 'CANCELLED'
            )
        ) {
            return false;
        }

        if (
            $action === 'UPDATED' &&
            (
                $oldStatus !== 'PENDING' ||
                $newStatus !== 'PENDING'
            )
        ) {
            return false;
        }

        // CHECK IF ORDER EXISTS
        $order = $this->orderRepo
            ->getOrderById($orderId);

        if ($order === false) {
            return false;
        }

        // SAVE HISTORY RECORD
        return $this->orderHistoryRepo
            ->createOrderHistory(
                $orderId,
                $userId,
                $action,
                $oldStatus,
                $newStatus,
                $remarks
            );
    }

    // ==========================================
    // COUNT HISTORY BY ORDER
    // ==========================================

    public function countHistoryByOrderId(
        int $orderId
    ): int {
        if ($orderId <= 0) {
            return 0;
        }

        return $this->orderHistoryRepo
            ->countHistoryByOrderId($orderId);
    }
}

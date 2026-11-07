<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\OrderHistoryService;
use App\Services\AuthService;

class OrderHistoryController
{
    private OrderHistoryService $orderHistoryService;
    private AuthService $authService;

    public function __construct(
        OrderHistoryService $orderHistoryService,
        AuthService $authService
    ) {
        $this->orderHistoryService = $orderHistoryService;
        $this->authService = $authService;
    }

    // ==========================================
    // ORDER HISTORY PAGE
    // GET /order-history
    // ==========================================

    public function index(): void
    {
        // GET ALL HISTORY RECORDS
        $history = $this->orderHistoryService
            ->getAllOrderHistory();

        // SUMMARY STATISTICS
        $totalTransactions = count($history);
        $createdCount = 0;
        $updatedCount = 0;
        $completedCount = 0;
        $cancelledCount = 0;

        foreach ($history as $record) {
            $action = strtoupper(
                (string) ($record['action'] ?? '')
            );

            switch ($action) {
                case 'CREATED':
                    $createdCount++;
                    break;

                case 'UPDATED':
                    $updatedCount++;
                    break;

                case 'COMPLETED':
                    $completedCount++;
                    break;

                case 'CANCELLED':
                    $cancelledCount++;
                    break;
            }
        }

        // OPTIONAL ACTION FILTER
        $actionFilter = strtoupper(
            trim((string) ($_GET['action'] ?? ''))
        );

        $allowedActions = [
            'CREATED',
            'UPDATED',
            'COMPLETED',
            'CANCELLED'
        ];

        if (
            $actionFilter !== '' &&
            in_array($actionFilter, $allowedActions, true)
        ) {
            $history = array_values(
                array_filter(
                    $history,
                    static fn(array $record): bool =>
                        $record['action'] === $actionFilter
                )
            );
        } else {
            $actionFilter = '';
        }

        // OPTIONAL ORDER FILTER
        $orderId = filter_input(
            INPUT_GET,
            'order_id',
            FILTER_VALIDATE_INT
        );

        if (
            $orderId !== null &&
            $orderId !== false &&
            $orderId > 0
        ) {
            $history = array_values(
                array_filter(
                    $history,
                    static fn(array $record): bool =>
                        (int) $record['order_id'] === $orderId
                )
            );
        } else {
            $orderId = null;
        }

        // PASS DATA TO VIEW
        View::render('user/history/index', [
            'title' => 'Order History',

            'history' => $history,
            'totalTransactions' => $totalTransactions,
            'createdCount' => $createdCount,
            'updatedCount' => $updatedCount,
            'completedCount' => $completedCount,
            'cancelledCount' => $cancelledCount,

            'actionFilter' => $actionFilter,
            'selectedOrderId' => $orderId,

            'csrfToken' => $this->authService
                ->getCsrfToken()
        ]);
    }

    // ==========================================
    // VIEW SINGLE HISTORY RECORD
    // GET /order-history/view?id=1
    // ==========================================

    public function show(): void
    {
        $id = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$id || $id <= 0) {
            http_response_code(400);
            exit('Invalid history ID.');
        }

        $record = $this->orderHistoryService
            ->findOrderHistoryById($id);

        if ($record === null) {
            http_response_code(404);
            exit('History record not found.');
        }

        View::render('order-history/show', [
            'title' => 'Order History Details',
            'record' => $record
        ]);
    }
}

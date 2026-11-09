<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\StockMovementRepo;
use RuntimeException;

class StockMovementService
{
    private StockMovementRepo $stockMovementRepo;

    private ?NotificationService $notificationService;

    public function __construct(
        StockMovementRepo $stockMovementRepo,
        ?NotificationService $notificationService = null
    ) {
        $this->stockMovementRepo = $stockMovementRepo;
        $this->notificationService = $notificationService;
    }

    // GET ALL STOCK MOVEMENTS (BY USER)
    public function getAllStockMovements(?int $userId = null): array
    {
        return $this->stockMovementRepo->getAllStockMovements($userId);
    }

    // FIND MOVEMENT BY ID
    public function findStockMovementById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $movement = $this->stockMovementRepo
            ->getStockMovementById($id);

        return $movement === false ? null : $movement;
    }

    // GET MOVEMENTS BY PRODUCT
    public function getMovementsByProductId(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        return $this->stockMovementRepo
            ->getMovementsByProductId($productId);
    }

    // GET MOVEMENTS BY TYPE
    public function getMovementsByType(string $movementType): array
    {
        $movementType = strtoupper(trim($movementType));

        if (!in_array($movementType, ['IN', 'OUT'], true)) {
            return [];
        }

        return $this->stockMovementRepo
            ->getMovementsByType($movementType);
    }

    // CREATE STOCK MOVEMENT (BY USER)
    public function createStockMovement(
        int $userId,
        int $productId,
        string $movementType,
        int $quantity,
        string $remarks
    ): bool {

        $movementType = strtoupper(trim($movementType));
        $remarks = trim($remarks);

        // VALIDATE INPUT
        if (
            $productId <= 0 ||
            $quantity <= 0 ||
            !in_array($movementType, ['IN', 'OUT'], true)
        ) {
            return false;
        }

        // UPDATE STOCK AND SAVE HISTORY
        $result = $this->stockMovementRepo->recordStockMovement(
            $userId,
            $productId,
            $movementType,
            $quantity,
            $remarks
        );

        if ($result) {
            $this->notifyActor($productId, $movementType, $quantity, $remarks);
        }

        return $result;
    }

    // ==========================================
    // EMAIL NOTIFICATIONS (BREVO)
    // Only "Stock movement" and "Order" events notify users
    // (the settings toggle gates delivery). Failures are
    // swallowed by NotificationService.
    // ==========================================

    private function notifyActor(
        int $productId,
        string $movementType,
        int $quantity,
        string $remarks = ''
    ): void {
        $actorId = (int) ($_SESSION['user_id'] ?? 0);

        if ($actorId <= 0 || $this->notificationService === null) {
            return;
        }

        $direction = $movementType === 'IN' ? 'IN' : 'OUT';

        $this->notificationService->send(
            $actorId,
            NotificationService::TYPE_STOCK_MOVEMENT,
            "Stock {$direction}: {$quantity} unit" . ($quantity === 1 ? '' : 's') . " (product #{$productId})",
            "Stock movement recorded",
            [
                "You recorded a stock movement of {$quantity} unit" . ($quantity === 1 ? '' : 's') . " {$direction} for product #{$productId}." . ($remarks !== '' ? ' Remarks: ' . $remarks : ''),
                "You can review the details in the Stock Movement page.",
            ],
            [],
            [
                "label" => "Open stock movement",
                "url" => self::appBaseUrl() . "/stock-movement"
            ]
        );
    }

    private static function appBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\StockMovementService;
use App\Core\View;

class StockMovementController
{
    private StockMovementService $stockMovementService;

    public function __construct(
        StockMovementService $stockMovementService
    ) {
        $this->stockMovementService = $stockMovementService;
    }

    // ==========================================
    // SHOW STOCK MOVEMENTS PAGE
    // ==========================================

    public function showStockMovements(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        $movements = $this->stockMovementService
            ->getAllStockMovements($userId);

        View::render("user/stock-movement/index", [
            "title" => "Stock Movements",
            "movements" => $movements
        ]);
    }

    // ==========================================
    // GET ALL MOVEMENTS
    // ==========================================

    public function getAllStockMovements(): array
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        return $this->stockMovementService
            ->getAllStockMovements($userId);
    }

    // ==========================================
    // FIND MOVEMENT BY ID
    // ==========================================

    public function findStockMovementById(int $id): ?array
    {
        return $this->stockMovementService
            ->findStockMovementById($id);
    }

    // ==========================================
    // GET MOVEMENTS BY PRODUCT
    // ==========================================

    public function getMovementsByProductId(int $productId): array
    {
        return $this->stockMovementService
            ->getMovementsByProductId($productId);
    }

    // ==========================================
    // GET MOVEMENTS BY TYPE
    // ==========================================

    public function getMovementsByType(string $movementType): array
    {
        return $this->stockMovementService
            ->getMovementsByType($movementType);
    }

    // ==========================================
    // CREATE STOCK MOVEMENT
    // ==========================================

    public function createStockMovement(
        int $productId,
        string $movementType,
        int $quantity,
        string $remarks
    ): bool {
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        return $this->stockMovementService
            ->createStockMovement(
                $userId,
                $productId,
                $movementType,
                $quantity,
                $remarks
            );
    }
}

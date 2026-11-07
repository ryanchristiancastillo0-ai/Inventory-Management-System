<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProductService;
use App\Services\CategoryService;
use App\Services\StockMovementService;
use App\Services\AuthService;
use App\Core\View;

class DashboardController
{
    private ProductService $productService;
    private CategoryService $categoryService;
    private StockMovementService $stockMovementService;
    private AuthService $authService;

    public function __construct(
        ProductService $productService,
        CategoryService $categoryService,
        StockMovementService $stockMovementService,
        AuthService $authService
    ) {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->stockMovementService = $stockMovementService;
        $this->authService = $authService;
    }

    // ==========================================
    // DASHBOARD OVERVIEW
    // ==========================================

    public function index(): void
    {
        // FETCH DATA FROM EXISTING SERVICES
        $products = $this->productService->getAllProducts();
        $categories = $this->categoryService->getAllCategories();
        $lowStockProducts = $this->productService->getLowStockProducts();
        $stockMovements = $this->stockMovementService->getAllStockMovements();

        // SUMMARY COUNTERS
        $totalProducts = count($products);
        $totalCategories = count($categories);
        $totalLowStock = count($lowStockProducts);
        $totalMovements = count($stockMovements);

        // STOCK AND INVENTORY VALUE
        $totalStock = 0;
        $totalInventoryValue = 0.0;
        $outOfStock = 0;

        foreach ($products as $product) {
            $quantity = (int) ($product['quantity'] ?? 0);
            $price = (float) ($product['price'] ?? 0);

            $totalStock += $quantity;
            $totalInventoryValue += $quantity * $price;

            if ($quantity === 0) {
                $outOfStock++;
            }
        }

        // STOCK MOVEMENT SUMMARY
        $totalStockIn = 0;
        $totalStockOut = 0;

        foreach ($stockMovements as $movement) {
            $type = strtoupper(
                (string) ($movement['movement_type'] ?? '')
            );

            $quantity = (int) ($movement['quantity'] ?? 0);

            if ($type === 'IN') {
                $totalStockIn += $quantity;
            }

            if ($type === 'OUT') {
                $totalStockOut += $quantity;
            }
        }

        // LATEST STOCK MOVEMENTS
        usort(
            $stockMovements,
            static function (array $a, array $b): int {
                $dateA = (string) ($a['created_at'] ?? '');
                $dateB = (string) ($b['created_at'] ?? '');

                $dateComparison = strcmp($dateB, $dateA);

                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                return (int) ($b['id'] ?? 0)
                    <=> (int) ($a['id'] ?? 0);
            }
        );

        $recentMovements = array_slice(
            $stockMovements,
            0,
            5
        );

        // RECENTLY ADDED PRODUCTS
        usort(
            $products,
            static function (array $a, array $b): int {
                $dateA = (string) ($a['created_at'] ?? '');
                $dateB = (string) ($b['created_at'] ?? '');

                $dateComparison = strcmp($dateB, $dateA);

                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                return (int) ($b['id'] ?? 0)
                    <=> (int) ($a['id'] ?? 0);
            }
        );

        $recentProducts = array_slice(
            $products,
            0,
            5
        );

        // DATA FOR DASHBOARD VIEW
        View::render('user/dashboard/index', [
            'title' => 'Dashboard Overview',

            'authService' => $this->authService,

            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalLowStock' => $totalLowStock,
            'totalMovements' => $totalMovements,

            'totalStock' => $totalStock,
            'totalInventoryValue' => $totalInventoryValue,
            'outOfStock' => $outOfStock,

            'totalStockIn' => $totalStockIn,
            'totalStockOut' => $totalStockOut,

            'lowStockProducts' => $lowStockProducts,
            'recentMovements' => $recentMovements,
            'recentProducts' => $recentProducts
        ]);
    }
}

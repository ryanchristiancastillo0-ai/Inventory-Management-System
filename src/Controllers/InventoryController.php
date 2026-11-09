<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AuthService;
use App\Services\ProductService;
use App\Services\CategoryService;
use App\Services\StockMovementService;
use Throwable;

class InventoryController
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
    // INVENTORY OVERVIEW PAGE
    // GET /inventory
    // ==========================================

    public function index(): void
    {
        $products = $this->productService
            ->getAllProducts();

        $categories = $this->categoryService
            ->getAllCategories();

        $lowStockProducts = $this->productService
            ->getLowStockProducts();

        $totalProducts = count($products);
        $totalLowStock = count($lowStockProducts);

        $totalStock = 0;
        $outOfStock = 0;
        $inventoryValue = 0.0;

        foreach ($products as $product) {
            $quantity = (int) $product['quantity'];
            $price = (float) $product['price'];

            $totalStock += $quantity;
            $inventoryValue += $quantity * $price;

            if ($quantity === 0) {
                $outOfStock++;
            }
        }

        // Optional selected product details
        $selectedProduct = null;
        $productMovements = [];

        $productId = filter_input(
            INPUT_GET,
            'product_id',
            FILTER_VALIDATE_INT
        );

        if ($productId !== null && $productId !== false && $productId > 0) {
            $selectedProduct = $this->productService
                ->findProductById($productId);

            if ($selectedProduct !== null) {
                $productMovements = $this->stockMovementService
                    ->getMovementsByProductId($productId);
            }
        }

        View::render('user/inventory/index', [
            'title' => 'Inventory Management',

            'products' => $products,
            'categories' => $categories,

            'totalProducts' => $totalProducts,
            'totalLowStock' => $totalLowStock,
            'totalStock' => $totalStock,
            'outOfStock' => $outOfStock,
            'inventoryValue' => $inventoryValue,

            'lowStockProducts' => $lowStockProducts,
            'selectedProduct' => $selectedProduct,
            'productMovements' => $productMovements,

            'csrfToken' => $this->authService
                ->getCsrfToken()
        ]);
    }

    // ==========================================
    // VERIFY CSRF TOKEN
    // ==========================================

    private function verifyRequest(): bool
    {
        $token = $_POST['csrf_token'] ?? '';

        return is_string($token)
            && $this->authService
                ->verifyCsrfToken($token);
    }

    // ==========================================
    // REDIRECT WITH FLASH MESSAGE
    // ==========================================

    private function redirect(
        string $message,
        bool $success
    ): void {
        $_SESSION[$success ? 'success' : 'error'] =
            $message;

        header('Location: /inventory');
        exit;
    }

    // ==========================================
    // CREATE PRODUCT
    // POST /inventory/create
    // ==========================================

    public function store(): void
    {
        if (!$this->verifyRequest()) {
            http_response_code(403);
            exit('Invalid security token.');
        }

        $categoryId = filter_var(
            $_POST['category_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $sku = trim((string) ($_POST['sku'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim(
            (string) ($_POST['description'] ?? '')
        );

        $price = (string) ($_POST['price'] ?? '');

        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_INT
        );

        $threshold = filter_var(
            $_POST['low_stock_threshold'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $categoryId === false ||
            $categoryId === null ||
            $quantity === false ||
            $quantity === null ||
            $threshold === false ||
            $threshold === null
        ) {
            $this->redirect(
                'Invalid product information.',
                false
            );
        }

        try {
            $success = $this->productService
                ->createProduct(
                    $categoryId,
                    $sku,
                    $name,
                    $description,
                    $price,
                    $quantity,
                    $threshold
                );

            $this->redirect(
                $success
                    ? 'Product created successfully.'
                    : 'Unable to create product. Check the details or SKU.',
                $success
            );

        } catch (Throwable $e) {
            error_log($e->getMessage());

            $this->redirect(
                'Database error while creating product.',
                false
            );
        }
    }

    // ==========================================
    // UPDATE PRODUCT
    // POST /inventory/update
    // ==========================================

    public function update(): void
    {
        if (!$this->verifyRequest()) {
            http_response_code(403);
            exit('Invalid security token.');
        }

        $id = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $categoryId = filter_var(
            $_POST['category_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $sku = trim((string) ($_POST['sku'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim(
            (string) ($_POST['description'] ?? '')
        );

        $price = (string) ($_POST['price'] ?? '');

        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_INT
        );

        $threshold = filter_var(
            $_POST['low_stock_threshold'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            !$id ||
            !$categoryId ||
            $quantity === false ||
            $quantity === null ||
            $threshold === false ||
            $threshold === null
        ) {
            $this->redirect(
                'Invalid product information.',
                false
            );
        }

        try {
            $success = $this->productService
                ->updateProductById(
                    $id,
                    $categoryId,
                    $sku,
                    $name,
                    $description,
                    $price,
                    $quantity,
                    $threshold
                );

            $this->redirect(
                $success
                    ? 'Product updated successfully.'
                    : 'Unable to update product.',
                $success
            );

        } catch (Throwable $e) {
            error_log($e->getMessage());

            $this->redirect(
                'Database error while updating product.',
                false
            );
        }
    }

    // ==========================================
    // DELETE PRODUCT
    // POST /inventory/delete
    // ==========================================

    public function destroy(): void
    {
        if (!$this->verifyRequest()) {
            http_response_code(403);
            exit('Invalid security token.');
        }

        $id = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id || $id <= 0) {
            $this->redirect(
                'Invalid product ID.',
                false
            );
        }

        try {
            $success = $this->productService
                ->deleteProductById($id);

            $this->redirect(
                $success
                    ? 'Product deleted successfully.'
                    : 'Unable to delete product.',
                $success
            );

        } catch (Throwable $e) {
            error_log($e->getMessage());

            $this->redirect(
                'Unable to delete product. It may have stock history.',
                false
            );
        }
    }

    // ==========================================
    // STOCK IN / STOCK OUT
    // POST /inventory/stock
    // ==========================================

    public function recordStock(): void
    {
        if (!$this->verifyRequest()) {
            http_response_code(403);
            exit('Invalid security token.');
        }

        $productId = filter_var(
            $_POST['product_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $movementType = strtoupper(
            trim((string) ($_POST['movement_type'] ?? ''))
        );

        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_INT
        );

        $remarks = trim(
            (string) ($_POST['remarks'] ?? '')
        );

        if (
            !$productId ||
            $productId <= 0 ||
            !$quantity ||
            $quantity <= 0 ||
            !in_array(
                $movementType,
                ['IN', 'OUT'],
                true
            )
        ) {
            $this->redirect(
                'Invalid stock movement information.',
                false
            );
        }

        try {
            $userId = (int) ($_SESSION['user_id'] ?? 0);

            $success = $this->stockMovementService
                ->createStockMovement(
                    $userId,
                    $productId,
                    $movementType,
                    $quantity,
                    $remarks
                );

            $this->redirect(
                $success
                    ? 'Stock movement recorded successfully.'
                    : 'Unable to record stock movement. Check available stock.',
                $success
            );

        } catch (Throwable $e) {
            error_log($e->getMessage());

            $this->redirect(
                'Database error while recording stock movement.',
                false
            );
        }
    }
}

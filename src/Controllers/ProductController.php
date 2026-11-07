<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProductService;
use App\Services\ProductImageService;
use App\Services\CategoryService;
use App\Services\AuthService;
use App\Core\View;
class ProductController
{
    private ProductService $productService;

    public function __construct(ProductService $productService, private CategoryService $categoryService, private AuthService $authService, private ProductImageService $productImageService)
    {
        $this->productService = $productService;
    }

    // ==========================================
// PRODUCTS MANAGEMENT PAGE
// GET /products/manage
// ==========================================

public function index(): void
{
    // GET ALL PRODUCTS
    $products = $this->productService->getAllProducts();

    // GET LOW STOCK PRODUCTS
    $lowStockProducts = $this->productService->getLowStockProducts();

    // CALCULATE PRODUCT STATISTICS
    $totalProducts = count($products);
    $totalLowStock = count($lowStockProducts);
    $outOfStock = 0;
    $totalStock = 0;

    foreach ($products as $product) {
        $quantity = (int) ($product['quantity'] ?? 0);

        $totalStock += $quantity;

        if ($quantity === 0) {
            $outOfStock++;
        }
    }

    // RENDER PRODUCTS PAGE
    View::render('user/products/index', [
        'title' => 'Product Management',
        'products' => $products,
        'categories' => $this->categoryService->getAllCategories(),
        'csrfToken' => $this->authService->getCsrfToken(),
        'lowStockProducts' => $lowStockProducts,
        'totalProducts' => $totalProducts,
        'totalLowStock' => $totalLowStock,
        'outOfStock' => $outOfStock,
        'totalStock' => $totalStock
    ]);
}

    // GET ALL PRODUCTS
    public function getAllProducts(): array
    {
        return $this->productService->getAllProducts();
    }

    public function mutate(string $action): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !$this->authService->verifyCsrfToken($token)) {
            jsonResponse(['success' => false, 'message' => 'Your session expired. Reload the page and try again.'], 403);
        }
        try {
            $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
            if ($action === 'delete') {
                if ($id <= 0) {
                    jsonResponse(['success' => false, 'message' => 'Product not found.'], 404);
                }

                // Grab the photo path first so the file can follow the
                // row into the bin once the delete succeeds.
                $product = $this->findProductById($id);
                $result = $this->deleteProductById($id);

                if ($result && is_array($product)) {
                    $this->productImageService->delete($product['image_path'] ?? null);
                }

                jsonResponse(['success' => $result, 'message' => $result ? 'Product deleted.' : 'Product not found.'], $result ? 200 : 404);
            }
            foreach (['name', 'sku', 'description', 'price'] as $field) {
                if (!is_string($_POST[$field] ?? '')) throw new \InvalidArgumentException('Invalid product details.');
            }
            $name = trim($_POST['name'] ?? '');
            $sku = trim($_POST['sku'] ?? '');
            $price = $_POST['price'] ?? '';
            $quantity = filter_var($_POST['quantity'] ?? '', FILTER_VALIDATE_INT);
            $threshold = filter_var($_POST['low_stock_threshold'] ?? 10, FILTER_VALIDATE_INT);
            $category = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
            if ($name === '' || mb_strlen($name) > 150 || $sku === '' || mb_strlen($sku) > 50) {
                throw new \InvalidArgumentException('Enter a product name (up to 150 characters) and SKU (up to 50 characters).');
            }
            if ($quantity === false || $threshold === false || $quantity < 0 || $threshold < 0 || !is_numeric($price) || (float) $price < 0 || (float) $price > 99999999.99) {
                throw new \InvalidArgumentException('Enter a valid price and non-negative whole numbers for stock and threshold.');
            }
            if (!$category || !$this->categoryService->findCategoryById($category)) {
                throw new \InvalidArgumentException('Select an existing category or create one first.');
            }
            $existing = $this->findProductBySku($sku);
            if ($existing && ($action !== 'update' || (int) $existing['id'] !== $id)) {
                throw new \InvalidArgumentException('This SKU is already in use. Choose a different SKU.');
            }

            /* ---------- Product photo (optional, multipart) ----------
               - create: store a new image if one was picked
               - update: ''  -> no change (checkbox/file untouched)
                          'x' -> new file uploaded (replace + delete old)
                          'remove' -> clear the photo and delete the file */
            $imageIntent = '';
            $newImagePath = null;
            $currentProduct = $action === 'update' ? $this->findProductById((int) $id) : null;

            if ($action === 'update' && $currentProduct === null) {
                jsonResponse(['success' => false, 'message' => 'Product not found.'], 404);
            }

            if (($_POST['remove_image'] ?? '') === '1') {
                $imageIntent = 'remove';
            } elseif (
                isset($_FILES['image'])
                && is_array($_FILES['image'])
                && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            ) {
                $stored = $this->productImageService->store($_FILES['image']);

                if (!$stored['success']) {
                    jsonResponse(['success' => false, 'message' => $stored['message']], 422);
                }

                $imageIntent = 'replace';
                $newImagePath = $stored['path'];
            }

            $createArgs = $action === 'create'
                ? [$category, $sku, $name, $_POST['description'] ?? '', $price, $quantity, $threshold, $newImagePath]
                : [$category, $sku, $name, $_POST['description'] ?? '', $price, $quantity, $threshold];

            if ($action === 'update') {
                $result = $this->updateProductById((int) $id, ...$createArgs);

                if ($result) {
                    // DB update confirmed — now sync the photo column.
                    $result = $this->syncProductImage(
                        (int) $id,
                        $imageIntent,
                        $newImagePath,
                        is_array($currentProduct) ? ($currentProduct['image_path'] ?? null) : null
                    );
                } else {
                    // Main update failed before any image change was
                    // recorded — roll back the just-stored file.
                    if ($imageIntent === 'replace' && $newImagePath !== null) {
                        $this->productImageService->delete($newImagePath);
                    }
                }
            } else {
                $result = $this->createProduct(...$createArgs);
            }

            if (is_array($currentProduct) && $result && ($imageIntent === 'replace' || $imageIntent === 'remove')) {
                // Old file goes only after the DB row is confirmed updated.
                $this->productImageService->delete($currentProduct['image_path'] ?? null);
            }

            jsonResponse(['success' => $result, 'message' => $result ? 'Product saved.' : 'Product could not be saved.'], $result ? ($action === 'update' ? 200 : 201) : 422);
        } catch (\InvalidArgumentException $error) {
            jsonResponse(['success' => false, 'message' => $error->getMessage()], 422);
        } catch (\Throwable $error) {
            error_log((string) $error);
            jsonResponse(['success' => false, 'message' => $action === 'delete' ? 'Unable to delete this product. It may be referenced by existing orders or stock movements.' : 'Unable to save the product. Please try again.'], 409);
        }
    }

    // FIND PRODUCT BY ID
    public function findProductById(int $id): ?array
    {
        return $this->productService->findProductById($id);
    }

    // FIND PRODUCT BY SKU
    public function findProductBySku(string $sku): ?array
    {
        return $this->productService->findProductBySku($sku);
    }

    // CREATE PRODUCT
    public function createProduct(
        int $categoryId,
        string $sku,
        string $name,
        string $description,
        string $price,
        int $quantity,
        int $lowStockThreshold,
        ?string $imagePath = null
    ): bool {
        $saved = $this->productService->createProduct(
            $categoryId,
            $sku,
            $name,
            $description,
            $price,
            $quantity,
            $lowStockThreshold,
            $imagePath
        );

        // If the insert fails AFTER the image was stored, remove the
        // orphaned file immediately.
        if (!$saved && $imagePath !== null) {
            $this->productImageService->delete($imagePath);
        }

        return $saved;
    }

    // UPDATE PRODUCT BY ID
    public function updateProductById(
        int $id,
        int $categoryId,
        string $sku,
        string $name,
        string $description,
        string $price,
        int $quantity,
        int $lowStockThreshold
    ): bool {
        return $this->productService->updateProductById(
            $id,
            $categoryId,
            $sku,
            $name,
            $description,
            $price,
            $quantity,
            $lowStockThreshold
        );
    }

    /**
     * Apply the requested image change after the main product update
     * succeeded. '' means no change, 'replace' points $imagePath to
     * the freshly stored file, 'remove' clears the column.
     * Returns false when the DB update fails so the caller can roll
     * the just-stored file back.
     */
    private function syncProductImage(int $id, string $intent, ?string $imagePath, ?string $currentPath): bool
    {
        if ($intent === '') {
            return true;
        }

        $target = $intent === 'remove' ? null : ($imagePath ?? $currentPath);

        if (!$this->productService->updateProductImage($id, $target)) {
            // DB failed — if a new file was stored, roll it back so no
            // orphan remains.
            if ($intent === 'replace' && $imagePath !== null && $imagePath !== $currentPath) {
                $this->productImageService->delete($imagePath);
            }
            return false;
        }

        return true;
    }

    // DELETE PRODUCT BY ID
    public function deleteProductById(int $id): bool
    {
        return $this->productService->deleteProductById($id);
    }

    // GET LOW STOCK PRODUCTS
    public function getLowStockProducts(): array
    {
        return $this->productService->getLowStockProducts();
    }
}

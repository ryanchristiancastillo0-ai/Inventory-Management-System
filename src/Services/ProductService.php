<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepo;

class ProductService
{
    private ProductRepo $productRepo;

    public function __construct(ProductRepo $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    // GET ALL PRODUCTS (BY USER)
    public function getAllProducts(?int $userId = null): array
    {
        return $this->productRepo->getAllProducts($userId);
    }

    // FIND PRODUCT BY ID
    public function findProductById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $product = $this->productRepo->getProductById($id);

        return $product === false ? null : $product;
    }

    // FIND PRODUCT BY SKU
    public function findProductBySku(string $sku): ?array
    {
        if (trim($sku) === "") {
            return null;
        }

        $product = $this->productRepo->findProductBySku(
            trim($sku)
        );

        return $product === false ? null : $product;
    }

    // CREATE PRODUCT (BY USER)
    public function createProduct(
        int $userId,
        int $categoryId,
        string $sku,
        string $name,
        string $description,
        string $price,
        int $quantity,
        int $lowStockThreshold,
        ?string $imagePath = null
    ): bool {

        if (
            $categoryId <= 0 ||
            trim($sku) === "" ||
            trim($name) === "" ||
            !is_numeric($price) ||
            (float) $price < 0 ||
            $quantity < 0 ||
            $lowStockThreshold < 0
        ) {
            return false;
        }

        // CHECK DUPLICATE SKU
        $existingProduct = $this->productRepo->findProductBySku(
            trim($sku)
        );

        if ($existingProduct !== false) {
            return false;
        }

        // SAVE PRODUCT
        return $this->productRepo->createProduct(
            $userId,
            $categoryId,
            trim($sku),
            trim($name),
            trim($description),
            $price,
            $quantity,
            $lowStockThreshold,
            $imagePath
        );
    }

    // UPDATE PRODUCT
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

        if (
            $id <= 0 ||
            $categoryId <= 0 ||
            trim($sku) === "" ||
            trim($name) === "" ||
            !is_numeric($price) ||
            (float) $price < 0 ||
            $quantity < 0 ||
            $lowStockThreshold < 0
        ) {
            return false;
        }

        // CHECK IF PRODUCT EXISTS
        $product = $this->productRepo->getProductById($id);

        if ($product === false) {
            return false;
        }

        // CHECK IF SKU BELONGS TO ANOTHER PRODUCT
        $existingSku = $this->productRepo->findProductBySku(
            trim($sku)
        );

        if (
            $existingSku !== false &&
            (int) $existingSku['id'] !== $id
        ) {
            return false;
        }

        // UPDATE PRODUCT
        return $this->productRepo->updateProductById(
            $id,
            $categoryId,
            trim($sku),
            trim($name),
            trim($description),
            $price,
            $quantity,
            $lowStockThreshold
        );
    }

    // UPDATE PRODUCT IMAGE PATH
    public function updateProductImage(int $id, ?string $imagePath): bool
    {
        if ($id <= 0) {
            return false;
        }

        return $this->productRepo->updateProductImage($id, $imagePath);
    }

    // DELETE PRODUCT
    public function deleteProductById(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        // CHECK IF PRODUCT EXISTS
        $product = $this->productRepo->getProductById($id);

        if ($product === false) {
            return false;
        }

        return $this->productRepo->deleteProductById($id);
    }

    // GET LOW STOCK PRODUCTS
    public function getLowStockProducts(?int $userId = null): array
    {
        return $this->productRepo->getLowStockProducts($userId);
    }
}

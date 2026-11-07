<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CategoryService;

class CategoryController
{
    private CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    // GET ALL CATEGORIES
    public function getAllCategories(): array
    {
        return $this->categoryService->getAllCategories();
    }

    // FIND CATEGORY BY ID
    public function findCategoryById(int $id): ?array
    {
        return $this->categoryService->findCategoryById($id);
    }

    // FIND CATEGORY BY NAME
    public function findCategoryByName(string $name): ?array
    {
        return $this->categoryService->findCategoryByName($name);
    }

    // CREATE CATEGORY
    public function createCategory(string $name): bool
    {
        return $this->categoryService->createCategory($name);
    }

    // UPDATE CATEGORY BY ID
    public function updateCategoryById(
        int $id,
        string $name
    ): bool {
        return $this->categoryService->updateCategoryById(
            $id,
            $name
        );
    }

    // DELETE CATEGORY BY ID
    public function deleteCategoryById(int $id): bool
    {
        return $this->categoryService->deleteCategoryById($id);
    }
}

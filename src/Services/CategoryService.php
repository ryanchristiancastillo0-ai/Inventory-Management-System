<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepo;

class CategoryService
{
    private CategoryRepo $categoryRepo;

    public function __construct(CategoryRepo $categoryRepo)
    {
        $this->categoryRepo = $categoryRepo;
    }

    // GET ALL CATEGORIES
    public function getAllCategories(): array
    {
        return $this->categoryRepo->getAllCategories();
    }

    // FIND CATEGORY BY ID
    public function findCategoryById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $category = $this->categoryRepo->getCategoryById($id);

        return $category === false ? null : $category;
    }

    // FIND CATEGORY BY NAME
    public function findCategoryByName(string $name): ?array
    {
        $name = trim($name);

        if ($name === "") {
            return null;
        }

        $category = $this->categoryRepo->findCategoryByName($name);

        return $category === false ? null : $category;
    }

    // CREATE CATEGORY
    public function createCategory(string $name): bool
    {
        $name = trim($name);

        if ($name === "") {
            return false;
        }

        // CHECK IF CATEGORY ALREADY EXISTS
        $existingCategory = $this->categoryRepo->findCategoryByName($name);

        if ($existingCategory !== false) {
            return false;
        }

        return $this->categoryRepo->createCategory($name);
    }

    // UPDATE CATEGORY BY ID
    public function updateCategoryById(int $id, string $name): bool
    {
        $name = trim($name);

        if ($id <= 0 || $name === "") {
            return false;
        }

        // CHECK IF CATEGORY EXISTS
        $category = $this->categoryRepo->getCategoryById($id);

        if ($category === false) {
            return false;
        }

        // CHECK IF NAME BELONGS TO ANOTHER CATEGORY
        $existingCategory = $this->categoryRepo->findCategoryByName($name);

        if (
            $existingCategory !== false &&
            (int) $existingCategory['id'] !== $id
        ) {
            return false;
        }

        return $this->categoryRepo->updateCategoryById($id, $name);
    }

    // DELETE CATEGORY BY ID
    public function deleteCategoryById(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        // CHECK IF CATEGORY EXISTS
        $category = $this->categoryRepo->getCategoryById($id);

        if ($category === false) {
            return false;
        }

        return $this->categoryRepo->deleteCategoryById($id);
    }
}

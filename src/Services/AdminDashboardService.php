<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepo;
use App\Repositories\ProductRepo;
use App\Repositories\CategoryRepo;

class AdminDashboardService
{
    private UserRepo $userRepo;
    private ProductRepo $productRepo;
    private CategoryRepo $categoryRepo;

    public function __construct(
        UserRepo $userRepo,
        ProductRepo $productRepo,
        CategoryRepo $categoryRepo
    ) {
        $this->userRepo = $userRepo;
        $this->productRepo = $productRepo;
        $this->categoryRepo = $categoryRepo;
    }

    // ==========================================
    // GET DASHBOARD STATISTICS
    // ==========================================

    public function getDashboardStats(): array
    {
        return [
            "totalUsers" => $this->userRepo
                ->countUsers(),

            "totalAdmins" => $this->userRepo
                ->countUsersByRole("ADMIN"),

            "totalRegularUsers" => $this->userRepo
                ->countUsersByRole("USER"),

            "newUsersThisMonth" => $this->userRepo
                ->countUsersThisMonth(),

            "totalProducts" => $this->productRepo
                ->countProducts(),

            "totalCategories" => $this->categoryRepo
                ->countCategories()
        ];
    }

    // ==========================================
    // GET MONTHLY USER REGISTRATIONS
    // ==========================================

    public function getMonthlyRegistrations(
        ?int $year = null
    ): array {
        $year = $year ?? (int) date("Y");

        $records = $this->userRepo
            ->getMonthlyRegistrations($year);

        $months = [
            "Jan", "Feb", "Mar", "Apr",
            "May", "Jun", "Jul", "Aug",
            "Sep", "Oct", "Nov", "Dec"
        ];

        // Default all months to zero
        $totals = array_fill(0, 12, 0);

        foreach ($records as $record) {
            $monthIndex = (int) $record["month"] - 1;

            if ($monthIndex >= 0 && $monthIndex < 12) {
                $totals[$monthIndex] = (int) $record["total"];
            }
        }

        return [
            "year" => $year,
            "labels" => $months,
            "data" => $totals
        ];
    }

    // ==========================================
    // GET RECENT REGISTERED USERS
    // ==========================================

    public function getRecentUsers(int $limit = 5): array
    {
        return $this->userRepo
            ->getRecentUsers($limit);
    }

    // ==========================================
    // GET ALL DASHBOARD DATA
    // ==========================================

    public function getDashboardData(): array
    {
        return [
            "stats" => $this->getDashboardStats(),

            "registrations" => $this
                ->getMonthlyRegistrations(),

            "recentUsers" => $this
                ->getRecentUsers()
        ];
    }
}

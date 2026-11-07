<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AdminDashboardService;

class AdminDashboardController
{
    private AdminDashboardService $adminDashboardService;

    public function __construct(
        AdminDashboardService $adminDashboardService
    ) {
        $this->adminDashboardService = $adminDashboardService;
    }

    // ==========================================
    // SHOW ADMIN DASHBOARD
    // ==========================================

    public function showDashboard(): void
    {
        $dashboardData = $this->adminDashboardService
            ->getDashboardData();

        View::render("admin/dashboard/index", [
            "title" => "Admin Dashboard",
            "stats" => $dashboardData["stats"],
            "registrations" => $dashboardData["registrations"],
            "recentUsers" => $dashboardData["recentUsers"]
        ], 'admin');
    }
}

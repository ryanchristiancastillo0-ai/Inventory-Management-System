<?php
/**
 * DASHBOARD PAGE
 * Rendered inside app.php. Sidebar and shell come from the layout.
 *
 * @var string $title
 * @var array  $products
 * @var array  $categories
 * @var array  $lowStockProducts
 * @var array  $stockMovements
 * @var int    $totalProducts
 * @var int    $totalCategories
 * @var int    $totalLowStock
 * @var int    $totalMovements
 * @var int    $totalStock
 * @var float  $totalInventoryValue
 * @var int    $outOfStock
 * @var int    $totalStockIn
 * @var int    $totalStockOut
 * @var array  $recentMovements
 * @var array  $recentProducts
 */

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>

<!-- ==========================================================
     COMPONENT: Topbar
     ========================================================== -->
<?php require __DIR__ . '/../../../components/user/topbar.php'; ?>

<main class="dashboard-content">

    <!-- ==================================================
         COMPONENT: Page header + actions
         ================================================== -->
    <section class="page-header d-flex flex-wrap align-items-end justify-content-between gap-3" aria-labelledby="pageTitle">
        <div class="min-w-0">
            <h1 class="page-title" id="pageTitle">Dashboard</h1>
            <p class="page-subtitle">Monitor your inventory, stock levels, and recent movements.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn-secondary" id="exportBtn">
                <i class="bi bi-download" aria-hidden="true"></i>Export
            </button>
            <a href="/products" class="btn-primary-app">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>Add product
            </a>
        </div>
    </section>

    <!-- ==================================================
         COMPONENT: KPI cards (dynamic from controller)
         ================================================== -->
      <?php  require __DIR__ . '/./components/kpi-cards.php'; ?>

    <!-- ==================================================
         COMPONENT: Inventory summary strip
         ================================================== -->
<?php require __DIR__ . '/./components/inventory-summary-strip.php'; ?>

    <!-- ==================================================
         COMPONENT: Recent activity table (from stock movements)
         ================================================== -->
    <?php require __DIR__ . '/./components/recent-activity-table.php'; ?>

</main>

<!-- ==========================================================
     COMPONENT: Toast stack (bottom-right notifications)
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<script src="/assets/js/export.js?v=2026111101"></script>
<script src="/assets/js/user/dashboard.js"></script>
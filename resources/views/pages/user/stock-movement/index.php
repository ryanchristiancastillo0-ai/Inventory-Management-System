<?php
/**
 * STOCK MOVEMENTS PAGE
 * Rendered inside app.php. Sidebar and shell come from the layout.
 *
 * Composed from components/ + external CSS/JS.
 *
 * Expected variables (all optional — falls back to empty defaults):
 *   @var array      $movements
 *   @var array      $products
 *   @var int        $totalMovements, $stockInCount, $stockOutCount,
 *                   $adjustmentCount, $todayCount
 *   @var string     $csrfToken
 */

require __DIR__ . '/components/stock-movement-setup.php';
?>

<!-- Page styles (moved out of the old inline <style> block) -->
<link rel="stylesheet" href="/assets/css/user/stock-movement.css?v=2026110801">

<?php require __DIR__ . '/components/topbar.php'; ?>

<main class="dashboard-content">
    <?php require __DIR__ . '/components/page-header.php'; ?>
    <?php require __DIR__ . '/components/filter-bar.php'; ?>
    <?php require __DIR__ . '/components/movements-table.php'; ?>
</main>

<?php require __DIR__ . '/components/new-movement-modal.php'; ?>

<!-- ==========================================================
     COMPONENT: Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<?php require __DIR__ . '/components/flash.php'; ?>

<script src="/assets/js/user/stock-movement/stock-movement-ui.js?v=2026110801"></script>
<script src="/assets/js/user/stock-movement/stock-movement-modals.js?v=2026110801"></script>
<script src="/assets/js/user/stock-movement/stock-movement-table.js?v=2026110801"></script>

<?php
/**
 * RESTOCK ORDERS PAGE
 * Rendered inside app.php. Sidebar and shell come from the layout.
 *
 * Composed from components/ + external CSS/JS.
 *
 * Expected variables (all optional — falls back to empty defaults):
 *   @var array      $orders
 *   @var array      $products
 *   @var int        $totalOrders, $pendingOrders, $orderedOrders,
 *                   $receivedOrders, $cancelledOrders
 *   @var float      $totalRestockCost
 *   @var array|null $selectedOrder
 *   @var array      $orderItems
 *   @var string     $csrfToken
 */

require __DIR__ . '/components/orders-setup.php';
?>

<!-- Page styles (moved out of the old inline <style> block) -->
<link rel="stylesheet" href="/assets/css/user/orders.css?v=2026110901">

<?php require __DIR__ . '/components/topbar.php'; ?>

<main class="dashboard-content">
    <?php require __DIR__ . '/components/page-header.php'; ?>
    <?php require __DIR__ . '/components/filter-bar.php'; ?>
    <?php require __DIR__ . '/components/orders-table.php'; ?>
</main>

<?php require __DIR__ . '/components/new-order-modal.php'; ?>
<?php require __DIR__ . '/components/order-detail-modal.php'; ?>
<?php require __DIR__ . '/components/suppliers-modal.php'; ?>
<?php require __DIR__ . '/components/supplier-edit-modal.php'; ?>
<?php require __DIR__ . '/components/supplier-delete-modal.php'; ?>

<!-- ==========================================================
     COMPONENT: Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<?php require __DIR__ . '/components/flash.php'; ?>

<script src="/assets/js/user/orders/orders-ui.js?v=2026110801"></script>
<script src="/assets/js/user/orders/orders-modals.js?v=2026110801"></script>
<script src="/assets/js/user/orders/orders-table.js?v=2026110801"></script>
<script src="/assets/js/user/orders/suppliers.js?v=2026110901"></script>

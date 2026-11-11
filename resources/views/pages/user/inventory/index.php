<?php
/**
 * INVENTORY PAGE
 * Rendered inside app.php. Sidebar and shell come from the layout.
 *
 * Inventory and Products operate on the same catalog. Share the real data,
 * forms and API handlers through the products components rather than
 * maintaining a separate editor. Only the title differs.
 *
 * @var array  $products    rows from ProductController::getAllProducts()
 * @var array  $categories  rows from CategoryController::getAllCategories()
 * @var string $csrfToken
 */

$catalogTitle = 'Inventory';
require __DIR__ . '/../products/components/catalog-setup.php';
?>

<!-- Page styles (shared with Products) -->
<link rel="stylesheet" href="/assets/css/user/catalog.css?v=2026110902">

<?php require __DIR__ . '/../products/components/topbar.php'; ?>

<main class="dashboard-content">
    <?php require __DIR__ . '/../products/components/page-header.php'; ?>
    <?php require __DIR__ . '/../products/components/stats-strip.php'; ?>
    <?php require __DIR__ . '/../products/components/filter-bar.php'; ?>
    <?php require __DIR__ . '/../products/components/catalog-table.php'; ?>
</main>

<?php require __DIR__ . '/../products/components/product-modal.php'; ?>
<?php require __DIR__ . '/../products/components/delete-modal.php'; ?>

<!-- ==========================================================
     COMPONENT: Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<script src="/assets/js/export.js?v=2026111101"></script>
<script src="/assets/js/user/products/products-ui.js?v=2026110801"></script>
<script src="/assets/js/user/products/products-catalog.js?v=2026110801"></script>

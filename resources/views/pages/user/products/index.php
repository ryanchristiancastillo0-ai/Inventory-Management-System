<?php
/**
 * PRODUCTS PAGE
 * Rendered inside app.php. Sidebar and shell come from the layout.
 *
 * Composed from components/ + shared CSS/JS with inventory/index.php:
 *
 * @var array  $products    rows from ProductController::getAllProducts()
 * @var array  $categories  rows from CategoryController::getAllCategories()
 * @var string $csrfToken
 */

$catalogTitle = 'Products';
require __DIR__ . '/components/catalog-setup.php';
?>

<!-- Page styles (moved out of the old inline <style> block) -->
<link rel="stylesheet" href="/assets/css/user/catalog.css?v=2026110902">

<?php require __DIR__ . '/components/topbar.php'; ?>

<main class="dashboard-content">
    <?php require __DIR__ . '/components/page-header.php'; ?>
    <?php require __DIR__ . '/components/stats-strip.php'; ?>
    <?php require __DIR__ . '/components/filter-bar.php'; ?>
    <?php require __DIR__ . '/components/catalog-table.php'; ?>
</main>

<?php require __DIR__ . '/components/product-modal.php'; ?>
<?php require __DIR__ . '/components/delete-modal.php'; ?>

<!-- ==========================================================
     COMPONENT: Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<script src="/assets/js/export.js?v=2026111101"></script>
<script src="/assets/js/user/products/products-ui.js?v=2026110801"></script>
<script src="/assets/js/user/products/products-catalog.js?v=2026110801"></script>

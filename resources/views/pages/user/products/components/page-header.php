<!-- ==========================================================
     COMPONENT: Page header
     ========================================================== -->
<section class="page-header" aria-labelledby="pageTitle">
    <div class="min-w-0">
        <h1 class="page-title" id="pageTitle"><?= $e($catalogTitle) ?></h1>
        <p class="page-subtitle">Manage your catalog, stock levels, and pricing.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn-secondary" id="exportBtn">
            <i class="bi bi-download" aria-hidden="true"></i>Export
        </button>
        <button type="button" class="btn-primary-app" id="addProductBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>Add product
        </button>
    </div>
</section>

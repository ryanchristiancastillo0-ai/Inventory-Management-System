<?php
/**
 * PRODUCTS PAGE
 * Renders inside app.php (sidebar + shell come from there).
 *
 * Expects these variables to be available. If not passed
 * explicitly, it falls back to the controllers in scope:
 *   @var array  $products    rows from ProductController::getAllProducts()
 *   @var array  $categories  rows from CategoryController::getAllCategories()
 *   @var string $csrfToken
 */

$products   = $products   ?? (isset($productController)   ? $productController->getAllProducts()     : []);
$categories = $categories ?? (isset($categoryController) ? $categoryController->getAllCategories() : []);
$csrfToken  = $csrfToken  ?? '';
$catalogTitle = $catalogTitle ?? 'Products';

// Category id → name lookup (tolerates id / category_id keys)
$catLookup = [];
foreach ($categories as $c) {
    $cid = (int) ($c['id'] ?? $c['category_id'] ?? 0);
    if ($cid) $catLookup[$cid] = (string) ($c['name'] ?? '');
}

// Compute stats
$totalProducts = count($products);
$inStock = $lowStock = $outOfStock = 0;
$totalValue = 0.0;
foreach ($products as $p) {
    $qty = (int) ($p['quantity'] ?? 0);
    $thr = (int) ($p['low_stock_threshold'] ?? 10);
    $prc = (float) ($p['price'] ?? 0);
    $totalValue += $prc * $qty;
    if ($qty <= 0)               $outOfStock++;
    elseif ($qty <= $thr)        $lowStock++;
    else                         $inStock++;
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>

<style>
    /* ==========================================================
       PRODUCTS PAGE — page-specific refinements
       ========================================================== */

    .page-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
    }
    .page-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    /* Stats strip */
    .stats-strip {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        margin-top: 24px;
    }
    .stat-item {
        padding: 20px 24px;
        border-right: 1px solid var(--border);
        min-width: 0;
    }
    .stat-item:last-child { border-right: 0; }
    .stat-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-secondary);
        margin: 0 0 6px;
    }
    .stat-value {
        font-size: 24px;
        font-weight: 600;
        letter-spacing: -0.6px;
        line-height: 1.15;
        margin: 0;
        color: var(--text);
        font-variant-numeric: tabular-nums;
    }
    .stat-value.is-low { color: var(--warning); }
    .stat-value.is-out { color: var(--danger); }

    /* Filter bar */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 24px;
    }
    .filter-bar__group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .filter-search { width: 260px; }
    .filter-count {
        font-size: 12px;
        color: var(--text-tertiary);
        font-variant-numeric: tabular-nums;
    }

    /* Custom checkbox */
    .checkbox-app {
        appearance: none;
        -webkit-appearance: none;
        width: 15px;
        height: 15px;
        border: 1px solid var(--border-strong);
        border-radius: 4px;
        background: var(--surface);
        cursor: pointer;
        position: relative;
        flex-shrink: 0;
        transition: background-color 120ms ease, border-color 120ms ease;
    }
    .checkbox-app:checked {
        background: var(--accent);
        border-color: var(--accent);
    }
    .checkbox-app:checked::after {
        content: "";
        position: absolute;
        left: 4px;
        top: 1px;
        width: 4px;
        height: 8px;
        border: solid #fff;
        border-width: 0 1.5px 1.5px 0;
        transform: rotate(45deg);
    }
    .checkbox-app:indeterminate {
        background: var(--accent);
        border-color: var(--accent);
    }
    .checkbox-app:indeterminate::after {
        content: "";
        position: absolute;
        left: 3px;
        top: 6px;
        width: 7px;
        height: 1.5px;
        background: #fff;
        border-radius: 1px;
    }
    .checkbox-app:focus-visible {
        outline: 2px solid var(--accent);
        outline-offset: 2px;
    }

    /* Table columns */
    .th-check, .td-check { width: 44px; }
    .th-actions, .td-actions { width: 48px; text-align: right; }
    .th-price, .th-stock { text-align: right; }

    /* Product cell */
    .product-cell-body { min-width: 0; }
    .product-name {
        font-weight: 500;
        color: var(--text);
        font-size: 13px;
        line-height: 1.3;
    }
    .product-meta {
        font-size: 11px;
        color: var(--text-tertiary);
        line-height: 1.3;
        margin-top: 2px;
    }

    /* SKU */
    .cell-sku {
        font-size: 12px;
        color: var(--text-tertiary);
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.01em;
    }

    /* Category chip */
    .chip {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        background: var(--surface-hover);
        color: var(--text-secondary);
        font-size: 11px;
        font-weight: 500;
        line-height: 1.4;
        white-space: nowrap;
    }

    /* Price */
    .cell-price {
        font-variant-numeric: tabular-nums;
        font-weight: 500;
        color: var(--text);
        white-space: nowrap;
    }

    /* Stock cell with mini progress bar */
    .stock-cell {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
        min-width: 60px;
    }
    .stock-num {
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
        font-variant-numeric: tabular-nums;
        line-height: 1;
    }
    .stock-num small {
        font-size: 11px;
        font-weight: 400;
        color: var(--text-tertiary);
        margin-left: 2px;
    }
    .stock-bar {
        width: 56px;
        height: 3px;
        border-radius: 2px;
        background: var(--surface-hover);
        overflow: hidden;
    }
    .stock-bar-fill {
        height: 100%;
        border-radius: 2px;
        background: var(--success);
        transition: width 400ms ease;
    }
    .stock-bar-fill.is-low { background: var(--warning); }
    .stock-bar-fill.is-out { background: var(--danger); }

    /* Row actions */
    .row-actions-btn {
        width: 28px;
        height: 28px;
        font-size: 14px;
    }

    /* Bulk bar */
    .bulk-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 20px;
        background: var(--surface-muted);
        border-bottom: 1px solid var(--border);
        font-size: 13px;
    }
    .bulk-bar[hidden] { display: none; }
    .bulk-bar__label { font-weight: 500; color: var(--text); }
    .bulk-bar__actions { display: flex; gap: 4px; }
    .btn-text.is-danger { color: var(--danger); }
    .btn-text.is-danger:hover { background: var(--danger-bg); color: var(--danger); }

    /* Empty state */
    .empty-state {
        padding: 72px 24px;
        text-align: center;
    }
    .empty-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: var(--surface-hover);
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 16px;
    }
    .empty-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--text);
        margin: 0 0 6px;
    }
    .empty-text {
        font-size: 13px;
        color: var(--text-secondary);
        margin: 0 0 20px;
    }

    /* Modal form refinements */
    #productModal .modal-content-app,
    #deleteModal .modal-content-app { border-radius: 16px; }
    #productModal .modal-body-app { max-height: 65vh; overflow-y: auto; }
    #productModal #createCategoryBtn { margin-top: 8px; }
    #productModal details summary { cursor: pointer; padding-block: 8px; color: var(--text-secondary); }
    @media (max-width: 575.98px) {
        #productModal .form-row.two-col { grid-template-columns: minmax(0, 1fr); }
    }
    .form-hint {
        font-size: 11px;
        color: var(--text-tertiary);
        margin-top: 4px;
    }

    @media (max-width: 767.98px) {
        .stats-strip { grid-template-columns: repeat(2, 1fr); }
        .stat-item:nth-child(2) { border-right: 0; }
        .stat-item:nth-child(1),
        .stat-item:nth-child(2) { border-bottom: 1px solid var(--border); }
        .filter-search { width: 100%; }
    }
</style>


<!-- ==========================================================
     COMPONENT: Topbar
     ========================================================== -->
<header class="topbar d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <button type="button" class="icon-btn d-xl-none" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open navigation menu">
            <i class="bi bi-list" style="font-size:20px" aria-hidden="true"></i>
        </button>
        <div class="breadcrumb-lite">Workspace <span style="color:var(--text-tertiary)">/</span> <strong><?= $e($catalogTitle) ?></strong></div>
    </div>
    <div class="d-flex align-items-center gap-2 gap-md-3">

        <!-- Search (wired to filter via #productSearch) -->
        <div class="search-wrap">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="productSearch" class="search-input" placeholder="Search products..." aria-label="Search products" autocomplete="off">
            <kbd>⌘K</kbd>
        </div>

        <!-- Notifications -->
        <div class="dropdown-app">
            <button type="button" class="icon-btn" id="notificationsBtn" data-dropdown-toggle="notificationsMenu" aria-label="Notifications" aria-haspopup="menu" aria-expanded="false">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="notif-dot" id="notifDot" aria-hidden="true"></span>
            </button>
            <div class="dropdown-menu-app notif-menu" id="notificationsMenu" role="menu">
                <div class="dropdown-label-app">Notifications</div>
                <?php $stockAlerts = array_slice(array_filter($products, static fn ($product) => (int) $product['quantity'] <= (int) $product['low_stock_threshold']), 0, 5); ?>
                <?php foreach ($stockAlerts as $alert): ?>
                    <a class="dropdown-item-app" href="/products?search=<?= $e(rawurlencode($alert['sku'])) ?>">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                        <?= $e($alert['name']) ?>: <?= (int) $alert['quantity'] ?> units remaining
                    </a>
                <?php endforeach; ?>
                <?php if (!$stockAlerts): ?><div class="dropdown-label-app">No stock alerts</div><?php endif; ?>
                <div class="dropdown-divider-app"></div>
                <button type="button" class="dropdown-item-app" id="markAllRead">
                    <i class="bi bi-check2-all" aria-hidden="true"></i>Mark all as read
                </button>
            </div>
        </div>

        <!-- User menu -->
        <div class="dropdown-app">
            <button type="button" class="avatar-btn" id="userMenuBtn" data-dropdown-toggle="userMenu" aria-label="Account menu" aria-haspopup="menu" aria-expanded="false">
                <span class="avatar" role="img" aria-label="Administrator">AD</span>
            </button>
            <div class="dropdown-menu-app" id="userMenu" role="menu">
                <div class="dropdown-label-app">Administrator</div>
                <button type="button" class="dropdown-item-app" data-user-action="profile"><i class="bi bi-person" aria-hidden="true"></i>Profile</button>
                <button type="button" class="dropdown-item-app" data-user-action="settings"><i class="bi bi-gear" aria-hidden="true"></i>Settings</button>
                <div class="dropdown-divider-app"></div>
                <button type="button" class="dropdown-item-app is-danger" data-user-action="logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sign out</button>
            </div>
        </div>
    </div>
</header>


<main class="dashboard-content">

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

    <!-- ==========================================================
         COMPONENT: Stats strip (server-computed)
         ========================================================== -->
    <section class="stats-strip" aria-label="Product statistics">
        <div class="stat-item">
            <p class="stat-label">Total products</p>
            <p class="stat-value"><?= number_format($totalProducts) ?></p>
        </div>
        <div class="stat-item">
            <p class="stat-label">In stock</p>
            <p class="stat-value"><?= number_format($inStock) ?></p>
        </div>
        <div class="stat-item">
            <p class="stat-label">Low stock</p>
            <p class="stat-value<?= $lowStock > 0 ? ' is-low' : '' ?>"><?= number_format($lowStock) ?></p>
        </div>
        <div class="stat-item">
            <p class="stat-label">Out of stock</p>
            <p class="stat-value<?= $outOfStock > 0 ? ' is-out' : '' ?>"><?= number_format($outOfStock) ?></p>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Filter bar
         ========================================================== -->
    <section class="filter-bar" aria-label="Filter products">
        <div class="filter-bar__group">
            <div class="search-wrap filter-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="filterSearch" class="search-input" placeholder="Filter by name or SKU..." aria-label="Filter products" autocomplete="off">
            </div>

            <!-- Category filter -->
            <div class="dropdown-app">
                <button type="button" class="btn-secondary" id="categoryFilterBtn" data-dropdown-toggle="categoryFilterMenu" aria-haspopup="menu" aria-expanded="false">
                    <i class="bi bi-tags" aria-hidden="true"></i>
                    <span id="categoryFilterLabel">All categories</span>
                    <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
                </button>
                <div class="dropdown-menu-app" id="categoryFilterMenu" role="menu">
                    <div class="dropdown-label-app">Category</div>
                    <button type="button" class="dropdown-item-app" data-category="0">All categories</button>
                    <?php foreach ($categories as $c):
                        $cid = (int) ($c['id'] ?? $c['category_id'] ?? 0);
                        $cname = (string) ($c['name'] ?? '');
                        if (!$cid) continue;
                    ?>
                        <button type="button" class="dropdown-item-app" data-category="<?= $cid ?>"><?= $e($cname) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Status filter -->
            <div class="dropdown-app">
                <button type="button" class="btn-secondary" id="statusFilterBtn" data-dropdown-toggle="statusFilterMenu" aria-haspopup="menu" aria-expanded="false">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    <span id="statusFilterLabel">All status</span>
                    <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
                </button>
                <div class="dropdown-menu-app" id="statusFilterMenu" role="menu">
                    <div class="dropdown-label-app">Status</div>
                    <button type="button" class="dropdown-item-app" data-status="">All status</button>
                    <button type="button" class="dropdown-item-app" data-status="in">In stock</button>
                    <button type="button" class="dropdown-item-app" data-status="low">Low stock</button>
                    <button type="button" class="dropdown-item-app" data-status="out">Out of stock</button>
                </div>
            </div>
        </div>

        <div class="filter-bar__group">
            <span class="filter-count" id="filterCount"><?= number_format($totalProducts) ?> product<?= $totalProducts === 1 ? '' : 's' ?></span>
            <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
                <i class="bi bi-x" aria-hidden="true"></i>Clear
            </button>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Products table
         ========================================================== -->
    <section class="section-gap">
        <div class="panel">

            <!-- Bulk action bar (shown when rows are selected) -->
            <div class="bulk-bar" id="bulkBar" hidden>
                <span class="bulk-bar__label"><span id="bulkCount">0</span> selected</span>
                <div class="bulk-bar__actions">
                    <button type="button" class="btn-text" id="bulkExportBtn"><i class="bi bi-download" aria-hidden="true"></i>Export</button>
                    <button type="button" class="btn-text is-danger" id="bulkDeleteBtn"><i class="bi bi-trash" aria-hidden="true"></i>Delete</button>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                    <h3 class="empty-title">No products yet</h3>
                    <p class="empty-text">Add your first product to start tracking inventory.</p>
                    <button type="button" class="btn-primary-app" onclick="document.getElementById('addProductBtn').click()">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>Add first product
                    </button>
                </div>
            <?php endif; ?>
                <div class="table-responsive" <?= empty($products) ? 'hidden' : '' ?>>
                    <table class="table activity-table align-middle">
                        <caption>Product catalog with name, SKU, category, price, stock and status.</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="th-check">
                                    <input type="checkbox" class="checkbox-app" id="selectAll" aria-label="Select all products">
                                </th>
                                <th scope="col">Product</th>
                                <th scope="col">SKU</th>
                                <th scope="col">Category</th>
                                <th scope="col" class="th-price">Price</th>
                                <th scope="col" class="th-stock">Stock</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="th-actions"></th>
                            </tr>
                        </thead>
                        <tbody id="productBody">
                            <?php foreach ($products as $p):
                                $pid   = (int) ($p['id'] ?? 0);
                                $name  = (string) ($p['name'] ?? '');
                                $sku   = (string) ($p['sku'] ?? '');
                                $desc  = (string) ($p['description'] ?? '');
                                $image = (string) ($p['image_path'] ?? '');
                                $catId = (int) ($p['category_id'] ?? 0);
                                $catNm = $catLookup[$catId] ?? 'Uncategorized';
                                $price = (float) ($p['price'] ?? 0);
                                $qty   = (int) ($p['quantity'] ?? 0);
                                $thr   = (int) ($p['low_stock_threshold'] ?? 10);

                                if ($qty <= 0) {
                                    $statusKey = 'out'; $statusLabel = 'Out of stock';
                                    $statusCls = 'status-danger';
                                } elseif ($qty <= $thr) {
                                    $statusKey = 'low'; $statusLabel = 'Low stock';
                                    $statusCls = 'status-warning';
                                } else {
                                    $statusKey = 'in'; $statusLabel = 'In stock';
                                    $statusCls = 'status-success';
                                }

                                $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '?';

                                // Stock bar — scale to 3× the threshold so in-stock items read full
                                $maxBar = max($thr * 3, 30);
                                $barWidth = $maxBar > 0 ? min(100, ($qty / $maxBar) * 100) : 0;
                                $barCls = $statusKey === 'out' ? ' is-out' : ($statusKey === 'low' ? ' is-low' : '');
                            ?>
                                <tr
                                    data-id="<?= $pid ?>"
                                    data-name="<?= $e($name) ?>"
                                    data-sku="<?= $e($sku) ?>"
                                     data-category="<?= $catId ?>"
                                     data-category-name="<?= $e($catNm) ?>"
                                    data-status="<?= $statusKey ?>"
                                    data-price="<?= $e($price) ?>"
                                    data-quantity="<?= $qty ?>"
                                    data-threshold="<?= $thr ?>"
                                    data-description="<?= $e($desc) ?>"
                                    data-image="<?= $e($image) ?>"
                                >
                                    <td class="td-check">
                                        <input type="checkbox" class="checkbox-app row-check" aria-label="Select <?= $e($name) ?>">
                                    </td>
                                    <td>
                                        <div class="product-cell">
                                            <?php if ($image !== ''): ?>
                                                <img class="product-avatar product-avatar--img" src="<?= $e($image) ?>" alt="" loading="lazy">
                                            <?php else: ?>
                                                <span class="product-avatar" aria-hidden="true"><?= $e($initial) ?></span>
                                            <?php endif; ?>
                                            <div class="product-cell-body">
                                                <div class="product-name"><?= $e($name) ?></div>
                                                <?php if ($desc !== ''): ?>
                                                    <div class="product-meta"><?= $e(mb_strimwidth($desc, 0, 48, '…')) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="cell-sku"><?= $e($sku) ?></td>
                                    <td><span class="chip"><?= $e($catNm) ?></span></td>
                                    <td class="cell-price text-end">₱<?= number_format($price, 2) ?></td>
                                    <td class="text-end">
                                        <div class="stock-cell">
                                            <span class="stock-num">
                                                <?= number_format($qty) ?><small>units</small>
                                            </span>
                                            <div class="stock-bar">
                                                <div class="stock-bar-fill<?= $barCls ?>" style="width: <?= round($barWidth) ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="status <?= $statusCls ?>"><?= $e($statusLabel) ?></span></td>
                                    <td class="td-actions">
                                        <div class="dropdown-app">
                                            <button type="button" class="icon-btn row-actions-btn" data-dropdown-toggle="rowMenu-<?= $pid ?>" aria-label="Row actions" aria-haspopup="menu" aria-expanded="false">
                                                <i class="bi bi-three-dots" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu-app" id="rowMenu-<?= $pid ?>" role="menu">
                                                 <button type="button" class="dropdown-item-app" data-row-action="edit"><i class="bi bi-pencil" aria-hidden="true"></i>Edit</button>
                                                 <button type="button" class="dropdown-item-app" data-row-action="duplicate"><i class="bi bi-files" aria-hidden="true"></i>Duplicate</button>
                                                <div class="dropdown-divider-app"></div>
                                                <button type="button" class="dropdown-item-app is-danger" data-row-action="delete"><i class="bi bi-trash" aria-hidden="true"></i>Delete</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="no-results" id="noResults" role="status">No products match your filters.</div>

                <div class="table-footer" <?= empty($products) ? 'hidden' : '' ?>>
                    <span id="showingLabel">
                        Showing 1–<?= number_format($totalProducts) ?> of <?= number_format($totalProducts) ?> products
                    </span>
                </div>

        </div>
    </section>

</main>


<!-- ==========================================================
     COMPONENT: Add / Edit product modal (shared)
     ========================================================== -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <form id="productForm" novalidate enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="id" id="fieldId" value="">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="productModalTitle">Add product</h2>
                        <p class="modal-subtitle-app" id="productModalSubtitle">Create a new product record.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <p id="productFormError" class="form-hint" role="alert" style="color:var(--danger)" hidden></p>
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldName">Product name</label>
                            <input class="form-control-app" type="text" id="fieldName" name="name" required placeholder="e.g. Wireless Mouse">
                        </div>
                    </div>

                    <div class="form-row two-col">
                        <div>
                            <label class="form-label-app" for="fieldSku">SKU</label>
                            <input class="form-control-app" type="text" id="fieldSku" name="sku" required placeholder="WM-0000">
                        </div>
                        <div>
                            <label class="form-label-app" for="fieldCategory">Category</label>
                            <select class="form-select-app" id="fieldCategory" name="category_id" required>
                                <option value="">Select…</option>
                                <?php foreach ($categories as $c):
                                    $cid = (int) ($c['id'] ?? $c['category_id'] ?? 0);
                                    $cname = (string) ($c['name'] ?? '');
                                    if (!$cid) continue;
                                ?>
                                    <option value="<?= $cid ?>"><?= $e($cname) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <details class="form-hint">
                                <summary>Add a category</summary>
                                <label class="form-label-app" for="newCategoryName">Category name</label>
                                <input class="form-control-app" id="newCategoryName" maxlength="100" placeholder="e.g. Accessories">
                                <button type="button" class="btn-secondary" id="createCategoryBtn">Create category</button>
                            </details>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldDescription">Description <span style="font-weight:400;color:var(--text-tertiary)">(optional)</span></label>
                            <textarea class="form-control-app" id="fieldDescription" name="description" rows="2" placeholder="Short description of the product" style="height:auto;padding:10px 12px;resize:vertical"></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldImage">Photo <span style="font-weight:400;color:var(--text-tertiary)">(optional)</span></label>
                            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                                <img id="fieldImagePreview" class="product-avatar product-avatar--img" src="" alt=""
                                     style="width:52px;height:52px;border-radius:10px" hidden>
                                <span class="product-avatar" id="fieldImageFallback" aria-hidden="true"
                                      style="width:52px;height:52px;border-radius:10px;font-size:18px">?</span>
                                <div style="display:flex;flex-direction:column;gap:8px;min-width:200px;flex:1">
                                    <input class="form-control-app" type="file" id="fieldImage" name="image"
                                           accept="image/jpeg,image/png,image/webp" style="height:auto;padding:8px 10px">
                                    <button type="button" class="btn-secondary" id="removeImageBtn" hidden>
                                        <i class="bi bi-trash3" aria-hidden="true"></i><span id="removeImageLabel">Remove photo</span>
                                    </button>
                                </div>
                                <input type="hidden" name="remove_image" id="fieldRemoveImage" value="">
                            </div>
                            <p class="form-hint">JPEG, PNG or WebP — up to 2 MB.</p>
                        </div>
                    </div>

                    <div class="form-row two-col">
                        <div>
                            <label class="form-label-app" for="fieldPrice">Price (₱)</label>
                            <input class="form-control-app" type="number" id="fieldPrice" name="price" min="0" step="0.01" required placeholder="0.00">
                        </div>
                        <div>
                            <label class="form-label-app" for="fieldQuantity">Quantity</label>
                            <input class="form-control-app" type="number" id="fieldQuantity" name="quantity" min="0" required placeholder="0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldThreshold">Low stock threshold</label>
                            <input class="form-control-app" type="number" id="fieldThreshold" name="low_stock_threshold" min="0" value="10" placeholder="10">
                            <p class="form-hint">Alert when quantity drops to or below this number.</p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app" id="productSubmitBtn">
                        <i class="bi bi-check2" aria-hidden="true"></i>Save product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ==========================================================
     COMPONENT: Delete confirmation modal
     ========================================================== -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <div class="modal-header-app">
                <div>
                    <h2 class="modal-title-app" id="deleteTitle">Delete product?</h2>
                    <p class="modal-subtitle-app">This action cannot be undone.</p>
                </div>
                <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body-app">
                <p style="margin:0;color:var(--text-secondary);font-size:14px">
                    Deleting <strong id="deleteProductName" style="color:var(--text)">this product</strong> will remove it from your inventory permanently.
                </p>
                <p id="deleteError" role="alert" style="color:var(--danger)" hidden></p>
            </div>
            <div class="modal-footer-app">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-primary-app" id="confirmDeleteBtn" style="background:var(--danger)">
                    <i class="bi bi-trash" aria-hidden="true"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ==========================================================
     COMPONENT: Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>


<script>
/* =========================================================
   TOAST HELPER
   ========================================================= */
(function () {
    var stack = document.getElementById('toastStack');
    window.showToast = function (message, icon) {
        if (!stack) return;
        var t = document.createElement('div');
        t.className = 'toast-app';
        t.innerHTML = '<i class="bi ' + (icon || 'bi-check2-circle') + '" aria-hidden="true"></i><span></span>';
        t.querySelector('span').textContent = message;
        stack.appendChild(t);
        setTimeout(function () {
            t.classList.add('toast-out');
            t.addEventListener('animationend', function () { t.remove(); });
        }, 2600);
    };
})();

/* =========================================================
   DROPDOWN MANAGER
   ========================================================= */
(function () {
    function closeAll() {
        document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
        document.querySelectorAll('[data-dropdown-toggle]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    }
    document.querySelectorAll('[data-dropdown-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var menu = document.getElementById(btn.dataset.dropdownToggle);
            if (!menu) return;
            var wasOpen = menu.classList.contains('is-open');
            closeAll();
            if (!wasOpen) { menu.classList.add('is-open'); btn.setAttribute('aria-expanded', 'true'); }
        });
    });
    document.querySelectorAll('.dropdown-menu-app').forEach(function (m) {
        m.addEventListener('click', function (e) { e.stopPropagation(); });
    });
    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });
})();

/* =========================================================
   NOTIFICATIONS + USER MENU
   ========================================================= */
(function () {
    document.querySelectorAll('[data-notif]').forEach(function (item) {
        item.addEventListener('click', function () {
            var t = item.querySelector('.notif-title');
            window.showToast(t ? t.textContent : 'Notification', 'bi-bell');
        });
    });
    document.querySelectorAll('#userMenu [data-user-action]').forEach(function (item) {
        item.addEventListener('click', function () {
            var action = item.dataset.userAction;
            document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            if (action === 'logout') window.showToast('Signed out (demo)', 'bi-box-arrow-right');
            else window.showToast('Opening ' + item.textContent.trim().toLowerCase() + '…', 'bi-arrow-right');
        });
    });
    var markAll = document.getElementById('markAllRead');
    if (markAll) {
        markAll.addEventListener('click', function () {
            var dot = document.getElementById('notifDot');
            if (dot) dot.style.display = 'none';
            document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            window.showToast('All notifications marked as read', 'bi-check2-all');
        });
    }
})();

/* =========================================================
   PRODUCTS PAGE
   ========================================================= */
(function () {
    var body = document.getElementById('productBody');
    if (!body) return;

    var empty       = document.getElementById('noResults');
    var showing     = document.getElementById('showingLabel');
    var countEl     = document.getElementById('filterCount');
    var selectAll   = document.getElementById('selectAll');
    var bulkBar     = document.getElementById('bulkBar');
    var bulkCount   = document.getElementById('bulkCount');
    var filterSearch  = document.getElementById('filterSearch');
    var categoryLabel = document.getElementById('categoryFilterLabel');
    var statusLabel   = document.getElementById('statusFilterLabel');
    var clearBtn      = document.getElementById('clearFiltersBtn');

    var currentSearch   = (new URLSearchParams(location.search).get('search') || '').trim().toLowerCase();
    var currentCategory = '0';
    var currentStatus   = '';

    /* ---- Filter + search ---- */
    function applyFilters() {
        var visible = 0;
        body.querySelectorAll('tr').forEach(function (row) {
            var catOk = currentCategory === '0' || row.dataset.category === currentCategory;
            var statusOk = !currentStatus || row.dataset.status === currentStatus;
            var searchOk = !currentSearch
                || (row.dataset.name + ' ' + row.dataset.sku).toLowerCase().indexOf(currentSearch) !== -1;
            var show = catOk && statusOk && searchOk;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        empty.style.display = visible || !body.children.length ? 'none' : 'block';
        countEl.textContent = visible + (visible === 1 ? ' product' : ' products');
        clearBtn.hidden = !(currentSearch || currentCategory !== '0' || currentStatus);

        if (visible === 0) {
            showing.textContent = 'No products';
        } else {
            var total = body.querySelectorAll('tr').length;
            showing.textContent = 'Showing 1–' + visible + ' of ' + total + ' products';
        }

        refreshBulkBar();
        refreshSelectAll();
    }

    filterSearch.addEventListener('input', function () {
        currentSearch = filterSearch.value.trim().toLowerCase();
        document.getElementById('productSearch').value = filterSearch.value;
        applyFilters();
    });
    document.getElementById('productSearch').addEventListener('input', function (event) {
        filterSearch.value = event.target.value;
        currentSearch = event.target.value.trim().toLowerCase();
        applyFilters();
    });

    function markFilterSelection(item) {
        var menu = item.closest('.dropdown-menu-app');
        if (!menu) return;
        menu.querySelectorAll('.dropdown-item-app.is-selected').forEach(function (el) {
            el.classList.remove('is-selected');
        });
        item.classList.add('is-selected');
    }

    document.querySelectorAll('#categoryFilterMenu [data-category]').forEach(function (item, index) {
        if (index === 0) item.classList.add('is-selected');
        item.addEventListener('click', function () {
            currentCategory = item.dataset.category;
            categoryLabel.textContent = item.textContent.trim();
            markFilterSelection(item);
            document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            applyFilters();
        });
    });

    document.querySelectorAll('#statusFilterMenu [data-status]').forEach(function (item, index) {
        if (index === 0) item.classList.add('is-selected');
        item.addEventListener('click', function () {
            currentStatus = item.dataset.status;
            statusLabel.textContent = item.textContent.trim();
            markFilterSelection(item);
            document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            applyFilters();
        });
    });

    clearBtn.addEventListener('click', function () {
        currentSearch = '';
        currentCategory = '0';
        currentStatus = '';
        filterSearch.value = '';
        document.getElementById('productSearch').value = '';
        categoryLabel.textContent = 'All categories';
        statusLabel.textContent = 'All status';
        document.querySelectorAll('#categoryFilterMenu [data-category]').forEach(function (el, i) {
            el.classList.toggle('is-selected', i === 0);
        });
        document.querySelectorAll('#statusFilterMenu [data-status]').forEach(function (el, i) {
            el.classList.toggle('is-selected', i === 0);
        });
        applyFilters();
    });

    /* ---- Selection ---- */
    function visibleChecks() {
        return Array.prototype.filter.call(body.querySelectorAll('.row-check'), function (c) {
            return c.closest('tr').style.display !== 'none';
        });
    }
    function refreshSelectAll() {
        var checks = visibleChecks();
        var checked = checks.filter(function (c) { return c.checked; });
        selectAll.checked = checks.length > 0 && checked.length === checks.length;
        selectAll.indeterminate = checked.length > 0 && checked.length < checks.length;
    }
    function refreshBulkBar() {
        var checked = body.querySelectorAll('.row-check:checked');
        bulkCount.textContent = checked.length;
        bulkBar.hidden = checked.length === 0;
    }
    selectAll.addEventListener('change', function () {
        visibleChecks().forEach(function (c) { c.checked = selectAll.checked; });
        refreshBulkBar();
    });
    body.addEventListener('change', function (e) {
        if (e.target.classList.contains('row-check')) {
            refreshBulkBar();
            refreshSelectAll();
        }
    });

    /* ---- Row actions (edit / delete) ---- */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-row-action]');
        if (!btn) return;
        var row = btn.closest('tr');
        var action = btn.dataset.rowAction;
        document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });

        if (action === 'edit') openEdit(row);
        else if (action === 'delete') openDelete(row);
        else if (action === 'duplicate') {
            openEdit(row);
            document.getElementById('fieldId').value = '';
            document.getElementById('fieldSku').value = '';
            /* A duplicate is a new record — the photo doesn't carry over;
               the user can pick a fresh one. */
            resetPhotoPicker('');
            titleEl.textContent = 'Duplicate product';
            subEl.textContent = 'Choose a unique SKU to save a new copy.';
        }
    }, true);

    /* ---- Modal: Add / Edit ---- */
    var modalEl = document.getElementById('productModal');
    var modal   = new bootstrap.Modal(modalEl);
    var form    = document.getElementById('productForm');
    var titleEl = document.getElementById('productModalTitle');
    var subEl   = document.getElementById('productModalSubtitle');
    var formError = document.getElementById('productFormError');

    function showFormError(message) {
        formError.textContent = message;
        formError.hidden = !message;
    }
    modalEl.addEventListener('shown.bs.modal', function () { document.getElementById('fieldName').focus(); });
    document.getElementById('createCategoryBtn').addEventListener('click', async function () {
        var button = this;
        var input = document.getElementById('newCategoryName');
        if (!input.value.trim()) { showFormError('Enter a category name.'); input.focus(); return; }
        button.disabled = true;
        try {
            var payload = new URLSearchParams({ name: input.value.trim(), csrf_token: form.elements.csrf_token.value });
            var response = await fetch('/categories', { method: 'POST', body: payload, headers: { Accept: 'application/json' } });
            var result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Category already exists or could not be created.');
            var categories = await (await fetch('/categories')).json();
            var select = document.getElementById('fieldCategory');
            select.replaceChildren(new Option('Select…', ''));
            categories.forEach(function (category) { select.add(new Option(category.name, category.id)); });
            var created = categories.find(function (category) { return category.name === input.value.trim(); });
            if (created) select.value = created.id;
            input.value = '';
            input.closest('details').open = false;
            showFormError('');
        } catch (error) { showFormError(error.message); }
        finally { button.disabled = false; }
    });

    function openAdd() {
        form.reset();
        showFormError('');
        document.getElementById('fieldId').value = '';
        resetPhotoPicker('');
        titleEl.textContent = 'Add product';
        subEl.textContent = 'Create a new product record.';
        modal.show();
    }

    function openEdit(row) {
        showFormError('');
        document.getElementById('fieldId').value        = row.dataset.id;
        document.getElementById('fieldName').value      = row.dataset.name;
        document.getElementById('fieldSku').value       = row.dataset.sku;
        document.getElementById('fieldCategory').value  = row.dataset.category;
        document.getElementById('fieldDescription').value = row.dataset.description || '';
        document.getElementById('fieldPrice').value     = row.dataset.price;
        document.getElementById('fieldQuantity').value  = row.dataset.quantity;
        document.getElementById('fieldThreshold').value = row.dataset.threshold;
        resetPhotoPicker(row.dataset.image || '');
        titleEl.textContent = 'Edit product';
        subEl.textContent = 'Update the product details.';
        modal.show();
    }

    document.getElementById('addProductBtn').addEventListener('click', openAdd);

    /* ---- Photo picker (optional product photo) ---- */
    var imageInput     = document.getElementById('fieldImage');
    var imagePreview   = document.getElementById('fieldImagePreview');
    var imageFallback  = document.getElementById('fieldImageFallback');
    var removeImageBtn = document.getElementById('removeImageBtn');
    var removeImageLabel = document.getElementById('removeImageLabel');
    var removeImageField = document.getElementById('fieldRemoveImage');
    var PHOTO_MAX_BYTES = 2 * 1024 * 1024;
    var PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    function setPhotoState(state) {
        /* state: 'none' | { path: string } | 'removed' */
        if (state === 'none') {
            imagePreview.hidden = true;
            imageFallback.hidden = false;
            removeImageBtn.hidden = true;
            removeImageField.value = '';
            imageInput.value = '';
        } else if (state === 'removed') {
            imagePreview.hidden = true;
            imageFallback.hidden = false;
            removeImageBtn.hidden = false;
            removeImageLabel.textContent = 'Undo';
            removeImageField.value = '1';
        } else {
            imagePreview.src = state.path;
            imagePreview.hidden = false;
            imageFallback.hidden = true;
            removeImageBtn.hidden = false;
            removeImageLabel.textContent = 'Remove photo';
            removeImageField.value = '';
        }
    }

    function resetPhotoPicker(currentPath) {
        removeImageLabel.textContent = 'Remove photo';
        removeImageField.value = '';
        imageInput.value = '';
        if (currentPath) {
            setPhotoState({ path: currentPath });
        } else {
            setPhotoState('none');
        }
    }

    imageInput.addEventListener('change', function () {
        var file = imageInput.files && imageInput.files[0];
        if (!file) return;
        var rowEl = document.getElementById('fieldId').value !== '' ? document.querySelector('tr[data-id="' + document.getElementById('fieldId').value + '"]') : null;
        var currentPath = rowEl && rowEl.dataset.image ? rowEl.dataset.image : '';
        if (PHOTO_TYPES.indexOf(file.type) === -1) {
            showFormError('Only JPEG, PNG or WebP images are supported.');
            resetPhotoPicker(currentPath);
            return;
        }
        if (file.size > PHOTO_MAX_BYTES) {
            showFormError('The image is too large. Maximum size is 2 MB.');
            resetPhotoPicker(currentPath);
            return;
        }
        showFormError('');
        setPhotoState({ path: URL.createObjectURL(file) });
    });

    removeImageBtn.addEventListener('click', function () {
        /* Toggle removal: first click flags it, second click undoes. */
        if (removeImageField.value === '1') {
            /* Undo — restore whatever was there before. */
            var rowEl = document.getElementById('fieldId').value !== '' ? document.querySelector('tr[data-id="' + document.getElementById('fieldId').value + '"]') : null;
            resetPhotoPicker(rowEl && rowEl.dataset.image ? rowEl.dataset.image : '');
        } else {
            imageInput.value = '';
            setPhotoState('removed');
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!form.reportValidity()) return;
        showFormError('');
        var btn = document.getElementById('productSubmitBtn');
        var orig = btn.innerHTML;
        var isEdit = document.getElementById('fieldId').value !== '';

        var payload = new FormData(form);
        var url = isEdit ? '/products/update' : '/products';

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i>Saving…';

        fetch(url, { method: 'POST', body: payload, credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (res) {
                if (res.status === 404) {
                    return { success: false, __missing: true };
                }
                return res.json();
            })
            .then(function (data) {
                if (data.__missing) {
                    window.showToast(
                        isEdit
                            ? 'Edit endpoint missing — add POST /products/update'
                            : 'Create endpoint missing — add POST /products',
                        'bi-info-circle'
                    );
                    btn.disabled = false;
                    btn.innerHTML = orig;
                    return;
                }
                if (data.success) {
                    window.showToast(isEdit ? 'Product updated' : 'Product created', 'bi-check2-circle');
                    modal.hide();
                    setTimeout(function () { window.location.reload(); }, 400);
                } else {
                    showFormError(data.message || 'Could not save product. Check the fields and use a unique SKU.');
                    btn.disabled = false;
                    btn.innerHTML = orig;
                }
            })
            .catch(function () {
                showFormError('Unable to save. Check your connection and try again.');
                btn.disabled = false;
                btn.innerHTML = orig;
            });
    });

    /* ---- Modal: Delete ---- */
    var deleteEl    = document.getElementById('deleteModal');
    var deleteModal = new bootstrap.Modal(deleteEl);
    var deleteName  = document.getElementById('deleteProductName');
    var deleteBtn   = document.getElementById('confirmDeleteBtn');
    var pendingIds  = [];
    var deleteError = document.getElementById('deleteError');

    function openDelete(row) {
        pendingIds = [row.dataset.id];
        deleteError.hidden = true;
        deleteName.textContent = row.dataset.name;
        deleteModal.show();
    }

    deleteBtn.addEventListener('click', async function () {
        if (!pendingIds.length) return;
        var orig = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i>Deleting…';
        deleteError.hidden = true;
        try {
            // Reuse the existing authorized single-delete endpoint, acknowledging each success.
            while (pendingIds.length) {
                var id = pendingIds[0];
                var fd = new URLSearchParams({ id: id, csrf_token: form.elements.csrf_token.value });
                var response = await fetch('/products/delete', { method: 'POST', body: fd, headers: { Accept: 'application/json' } });
                var result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Could not delete product.');
                pendingIds.shift();
                var row = body.querySelector('tr[data-id="' + id + '"]');
                if (row) row.remove();
            }
            window.location.reload();
        } catch (error) {
            deleteError.textContent = error.message + ' Any products already deleted will stay deleted.';
            deleteError.hidden = false;
            applyFilters();
        } finally {
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = orig;
        }
    });

    /* ---- Export (visible rows) ---- */
    function exportRows(rows) {
        if (!rows.length) { window.showToast('Nothing to export', 'bi-exclamation-circle'); return; }

        var csv = 'Name,SKU,Category,Price,Quantity,Threshold,Status\n';
        rows.forEach(function (r) {
            var vals = [
                r.dataset.name,
                r.dataset.sku,
                r.dataset.categoryName,
                r.dataset.price,
                r.dataset.quantity,
                r.dataset.threshold,
                r.dataset.status
            ].map(function (v) {
                v = String(v || '');
                if (/^[=+@\-\t\r]/.test(v)) v = "'" + v;
                return /[",\n]/.test(v) ? '"' + String(v).replace(/"/g, '""') + '"' : v;
            });
            csv += vals.join(',') + '\n';
        });
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'products-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
        window.showToast('Exported ' + rows.length + ' products', 'bi-download');
    }
    document.getElementById('exportBtn').addEventListener('click', function () {
        exportRows(Array.from(body.querySelectorAll('tr')).filter(function (row) { return row.style.display !== 'none'; }));
    });

    /* ---- Bulk actions ---- */
    document.getElementById('bulkExportBtn').addEventListener('click', function () {
        exportRows(Array.from(body.querySelectorAll('.row-check:checked')).map(function (input) { return input.closest('tr'); }));
    });
    document.getElementById('bulkDeleteBtn').addEventListener('click', function () {
        var checked = body.querySelectorAll('.row-check:checked');
        if (!checked.length) return;
        pendingIds = Array.from(checked).map(function (input) { return input.closest('tr').dataset.id; });
        deleteName.textContent = pendingIds.length + ' selected products';
        deleteError.hidden = true;
        deleteModal.show();
    });

    /* ---- ⌘K focuses search ---- */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            filterSearch.focus();
        }
    });

    /* ---- Init ---- */
    filterSearch.value = currentSearch;
    document.getElementById('productSearch').value = currentSearch;
    applyFilters();
})();
</script>

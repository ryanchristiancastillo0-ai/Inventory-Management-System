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

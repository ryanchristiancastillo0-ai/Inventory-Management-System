<!-- ==========================================================
     COMPONENT: Filter bar
     ========================================================== -->
<section class="filter-bar" aria-label="Filter orders">
    <div class="filter-bar__group">
        <div class="search-wrap filter-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="filterSearch" class="search-input" placeholder="Filter by order # or supplier..." aria-label="Filter orders" autocomplete="off">
        </div>

        <div class="dropdown-app">
            <button type="button" class="btn-secondary" id="statusFilterBtn" data-dropdown-toggle="statusFilterMenu" aria-haspopup="menu" aria-expanded="false">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <span id="statusFilterLabel">All statuses</span>
                <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu-app" id="statusFilterMenu" role="menu">
                <div class="dropdown-label-app">Order status</div>
                <button type="button" class="dropdown-item-app" data-status="">All statuses</button>
                <button type="button" class="dropdown-item-app" data-status="PENDING">Pending</button>
                <button type="button" class="dropdown-item-app" data-status="ORDERED">Ordered</button>
                <button type="button" class="dropdown-item-app" data-status="RECEIVED">Received</button>
                <button type="button" class="dropdown-item-app" data-status="CANCELLED">Cancelled</button>
            </div>
        </div>
    </div>

    <div class="filter-bar__group">
        <span class="filter-count" id="filterCount"><?= number_format($totalOrders) ?> order<?= $totalOrders === 1 ? '' : 's' ?></span>
        <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
            <i class="bi bi-x" aria-hidden="true"></i>Clear
        </button>
    </div>
</section>

<!-- ==========================================================
     COMPONENT: Filter bar
     ========================================================== -->
<section class="filter-bar" aria-label="Filter movements">
    <div class="filter-bar__group">
        <div class="search-wrap filter-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="filterSearch" class="search-input" placeholder="Filter by product, SKU or remarks..." aria-label="Filter movements" autocomplete="off">
        </div>

        <!-- Type filter — Bootstrap dropdown -->
        <div class="dropdown">
            <button type="button" class="btn-secondary" id="typeFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="d-inline-flex align-items-center gap-2">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    <span id="typeFilterLabel">All types</span>
                </span>
                <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu" id="typeFilterMenu" aria-labelledby="typeFilterBtn">
                <li><div class="dropdown-label-app">Movement type</div></li>
                <li><button type="button" class="dropdown-item active" data-type="" aria-current="true"><i class="bi bi-list-ul" aria-hidden="true"></i>All types</button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item" data-type="IN"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>Stock In</button></li>
                <li><button type="button" class="dropdown-item" data-type="OUT"><i class="bi bi-box-arrow-up" aria-hidden="true"></i>Stock Out</button></li>
                <li><button type="button" class="dropdown-item" data-type="ADJUSTMENT"><i class="bi bi-sliders" aria-hidden="true"></i>Adjustment</button></li>
                <li><button type="button" class="dropdown-item" data-type="RETURN"><i class="bi bi-arrow-return-left" aria-hidden="true"></i>Return</button></li>
                <li><button type="button" class="dropdown-item" data-type="DAMAGE"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i>Damage</button></li>
            </ul>
        </div>
    </div>

    <div class="filter-bar__group">
        <span class="filter-count" id="filterCount"><?= number_format($totalMovements) ?> movement<?= $totalMovements === 1 ? '' : 's' ?></span>
        <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
            <i class="bi bi-x" aria-hidden="true"></i>Clear
        </button>
    </div>
</section>

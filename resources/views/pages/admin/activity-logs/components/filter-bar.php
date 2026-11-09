<!-- ==========================================================
     Filter bar
     ========================================================== -->
<section class="filter-bar" aria-label="Filter activity logs">
    <div class="filter-bar__group">
        <div class="search-wrap filter-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search"
                   id="filterSearch"
                   class="search-input"
                   placeholder="Filter by actor, action, target or IP..."
                   aria-label="Filter activity logs"
                   autocomplete="off">
        </div>

        <!-- Action filter — Bootstrap dropdown -->
        <div class="dropdown">
            <button type="button" class="btn-secondary" id="actionFilterBtn"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <span class="d-inline-flex align-items-center gap-2">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    <span id="actionFilterLabel">All actions</span>
                </span>
                <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu" aria-labelledby="actionFilterBtn">
                <li><div class="dropdown-label-app">Action</div></li>
                <li>
                    <button type="button" class="dropdown-item active" data-action="">
                        <i class="bi bi-list-ul" aria-hidden="true"></i>All actions
                        <i class="bi bi-check2 dropdown-check" aria-hidden="true"></i>
                    </button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item" data-action="create"><i class="bi bi-plus-circle" aria-hidden="true"></i>Creates</button></li>
                <li><button type="button" class="dropdown-item" data-action="update"><i class="bi bi-pencil-square" aria-hidden="true"></i>Updates</button></li>
                <li><button type="button" class="dropdown-item" data-action="delete"><i class="bi bi-trash" aria-hidden="true"></i>Deletes</button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item" data-action="login"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Logins</button></li>
                <li><button type="button" class="dropdown-item" data-action="logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Logouts</button></li>
                <li><button type="button" class="dropdown-item" data-action="view"><i class="bi bi-eye" aria-hidden="true"></i>Views</button></li>
                <li><button type="button" class="dropdown-item" data-action="other"><i class="bi bi-circle" aria-hidden="true"></i>Other</button></li>
            </ul>
        </div>
    </div>

    <div class="filter-bar__group">
        <span class="filter-count" id="filterCount"><?= number_format(count($logs)) ?> entr<?= count($logs) === 1 ? 'y' : 'ies' ?> on this page</span>
        <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
            <i class="bi bi-x" aria-hidden="true"></i>Clear
        </button>
    </div>
</section>

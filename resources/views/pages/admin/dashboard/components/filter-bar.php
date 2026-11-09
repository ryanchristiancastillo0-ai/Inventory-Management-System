<!-- ==========================================================
     COMPONENT: Recent users — filter bar
     ========================================================== -->
<section class="filter-bar" aria-label="Filter recent users">
    <div class="filter-bar__group">
        <div class="search-wrap filter-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="filterSearch" class="search-input" placeholder="Filter by name, email or role..." aria-label="Filter users" autocomplete="off">
        </div>

        <!-- Role filter — Bootstrap dropdown -->
        <div class="dropdown">
            <button type="button" class="btn-secondary" id="roleFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="d-inline-flex align-items-center gap-2">
                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                    <span id="roleFilterLabel">All roles</span>
                </span>
                <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu" aria-labelledby="roleFilterBtn">
                <li><div class="dropdown-label-app">Role</div></li>
                <li><button type="button" class="dropdown-item active" data-role=""><i class="bi bi-list-ul" aria-hidden="true"></i>All roles<i class="bi bi-check2 dropdown-check"></i></button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item" data-role="admin"><i class="bi bi-shield-lock" aria-hidden="true"></i>Admin</button></li>
                <li><button type="button" class="dropdown-item" data-role="staff"><i class="bi bi-person-badge" aria-hidden="true"></i>Staff</button></li>
                <li><button type="button" class="dropdown-item" data-role="user"><i class="bi bi-person" aria-hidden="true"></i>Customer</button></li>
            </ul>
        </div>

        <!-- Status filter — Bootstrap dropdown -->
        <div class="dropdown">
            <button type="button" class="btn-secondary" id="statusFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="d-inline-flex align-items-center gap-2">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    <span id="statusFilterLabel">All statuses</span>
                </span>
                <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu" aria-labelledby="statusFilterBtn">
                <li><div class="dropdown-label-app">Status</div></li>
                <li><button type="button" class="dropdown-item active" data-status=""><i class="bi bi-list-ul" aria-hidden="true"></i>All statuses<i class="bi bi-check2 dropdown-check"></i></button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item" data-status="active"><i class="bi bi-check-circle" aria-hidden="true"></i>Active</button></li>
                <li><button type="button" class="dropdown-item" data-status="inactive"><i class="bi bi-pause-circle" aria-hidden="true"></i>Inactive</button></li>
                <li><button type="button" class="dropdown-item" data-status="pending"><i class="bi bi-hourglass-split" aria-hidden="true"></i>Pending</button></li>
                <li><button type="button" class="dropdown-item" data-status="suspended"><i class="bi bi-slash-circle" aria-hidden="true"></i>Suspended</button></li>
            </ul>
        </div>
    </div>

    <div class="filter-bar__group">
        <span class="filter-count" id="filterCount"><?= number_format(count($recentUsers)) ?> user<?= count($recentUsers) === 1 ? '' : 's' ?></span>
        <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
            <i class="bi bi-x" aria-hidden="true"></i>Clear
        </button>
    </div>
</section>

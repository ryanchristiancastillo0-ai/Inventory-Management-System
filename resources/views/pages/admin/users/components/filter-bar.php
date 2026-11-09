<!-- ==========================================================
     Filter bar
     ========================================================== -->
<section class="filter-bar" aria-label="Filter users">
    <div class="filter-bar__group">
        <div class="search-wrap filter-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search"
                   id="filterSearch"
                   class="search-input"
                   placeholder="Filter by username, email or role..."
                   aria-label="Filter users"
                   autocomplete="off">
        </div>

        <!-- Role filter — Bootstrap dropdown -->
        <div class="dropdown">
            <button type="button" class="btn-secondary" id="roleFilterBtn"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <span class="d-inline-flex align-items-center gap-2">
                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                    <span id="roleFilterLabel">All roles</span>
                </span>
                <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu" aria-labelledby="roleFilterBtn">
                <li><div class="dropdown-label-app">Role</div></li>
                <li>
                    <button type="button" class="dropdown-item active" data-role="">
                        <i class="bi bi-list-ul" aria-hidden="true"></i>All roles
                        <i class="bi bi-check2 dropdown-check" aria-hidden="true"></i>
                    </button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <button type="button" class="dropdown-item" data-role="admin">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>Admin
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item" data-role="user">
                        <i class="bi bi-person" aria-hidden="true"></i>User
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="filter-bar__group">
        <span class="filter-count" id="filterCount"><?= number_format(count($users)) ?> user<?= count($users) === 1 ? '' : 's' ?></span>
        <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
            <i class="bi bi-x" aria-hidden="true"></i>Clear
        </button>
    </div>
</section>

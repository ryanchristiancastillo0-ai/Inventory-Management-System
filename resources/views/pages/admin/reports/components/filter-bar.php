    <!-- ==========================================================
         Filter bar
         ========================================================== -->
    <section class="filter-bar" aria-label="Filter reports">
        <div class="filter-bar__group">
            <div class="search-wrap filter-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search"
                       id="filterSearch"
                       class="search-input"
                       placeholder="Filter by subject, reporter or details..."
                       aria-label="Filter reports"
                       autocomplete="off">
            </div>

            <!-- Status filter — Bootstrap dropdown -->
            <div class="dropdown">
                <button type="button" class="btn-secondary" id="statusFilterBtn"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-inline-flex align-items-center gap-2">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span id="statusFilterLabel">All statuses</span>
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="statusFilterBtn">
                    <li><div class="dropdown-label-app">Status</div></li>
                    <li>
                        <button type="button" class="dropdown-item active" data-status="">
                            <i class="bi bi-list-ul" aria-hidden="true"></i>All statuses
                            <i class="bi bi-check2 dropdown-check" aria-hidden="true"></i>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item" data-status="pending"><i class="bi bi-hourglass-split" aria-hidden="true"></i>Pending</button></li>
                    <li><button type="button" class="dropdown-item" data-status="progress"><i class="bi bi-arrow-repeat" aria-hidden="true"></i>In progress</button></li>
                    <li><button type="button" class="dropdown-item" data-status="resolved"><i class="bi bi-check2-circle" aria-hidden="true"></i>Resolved</button></li>
                    <li><button type="button" class="dropdown-item" data-status="dismissed"><i class="bi bi-slash-circle" aria-hidden="true"></i>Dismissed</button></li>
                </ul>
            </div>
        </div>

        <div class="filter-bar__group">
            <span class="filter-count" id="filterCount"><?= number_format(count($reports)) ?> report<?= count($reports) === 1 ? '' : 's' ?></span>
            <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
                <i class="bi bi-x" aria-hidden="true"></i>Clear
            </button>
        </div>
    </section>

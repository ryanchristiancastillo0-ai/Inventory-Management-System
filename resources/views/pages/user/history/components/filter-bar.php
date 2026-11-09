<!-- ==========================================================
     COMPONENT: Filter bar
     ========================================================== -->
<section class="filter-bar" aria-label="Filter order history">
    <div class="filter-bar__group">
        <div class="search-wrap filter-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="filterSearch" class="search-input" placeholder="Filter by order #, user or details..." aria-label="Filter order history" autocomplete="off">
        </div>

        <!-- Action filter — Bootstrap dropdown (server-side via links) -->
        <div class="dropdown">
            <button type="button" class="btn-secondary" id="actionFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="d-inline-flex align-items-center gap-2">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    <span><?= $e($actionFilterLabel) ?></span>
                </span>
                <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu" aria-labelledby="actionFilterBtn">
                <li>
                    <a class="dropdown-item <?= $actionFilter === '' ? 'active' : '' ?>" href="<?= $e($buildUrl(['action' => null])) ?>">
                        <i class="bi bi-list-ul" aria-hidden="true"></i>All actions
                        <?php if ($actionFilter === ''): ?><i class="bi bi-check2 dropdown-check" aria-hidden="true"></i><?php endif; ?>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item <?= $actionFilter === 'CREATED' ? 'active' : '' ?>" href="<?= $e($buildUrl(['action' => 'CREATED'])) ?>">
                        <i class="bi bi-plus-circle" aria-hidden="true"></i>Created
                        <?php if ($actionFilter === 'CREATED'): ?><i class="bi bi-check2 dropdown-check" aria-hidden="true"></i><?php endif; ?>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?= $actionFilter === 'UPDATED' ? 'active' : '' ?>" href="<?= $e($buildUrl(['action' => 'UPDATED'])) ?>">
                        <i class="bi bi-pencil-square" aria-hidden="true"></i>Updated
                        <?php if ($actionFilter === 'UPDATED'): ?><i class="bi bi-check2 dropdown-check" aria-hidden="true"></i><?php endif; ?>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?= $actionFilter === 'COMPLETED' ? 'active' : '' ?>" href="<?= $e($buildUrl(['action' => 'COMPLETED'])) ?>">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>Completed
                        <?php if ($actionFilter === 'COMPLETED'): ?><i class="bi bi-check2 dropdown-check" aria-hidden="true"></i><?php endif; ?>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item <?= $actionFilter === 'CANCELLED' ? 'active' : '' ?>" href="<?= $e($buildUrl(['action' => 'CANCELLED'])) ?>">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>Cancelled
                        <?php if ($actionFilter === 'CANCELLED'): ?><i class="bi bi-check2 dropdown-check" aria-hidden="true"></i><?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="filter-bar__group">
        <span class="filter-count" id="filterCount"><?= number_format(count($history)) ?> record<?= count($history) === 1 ? '' : 's' ?></span>
        <a href="/order-history" class="btn-text" id="clearFiltersBtn" <?= !$hasServerFilter ? 'hidden' : '' ?>>
            <i class="bi bi-x" aria-hidden="true"></i>Clear filters
        </a>
    </div>
</section>

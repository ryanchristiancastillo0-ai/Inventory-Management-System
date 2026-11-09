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

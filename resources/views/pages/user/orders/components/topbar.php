<!-- ==========================================================
     COMPONENT: Topbar
     ========================================================== -->
<header class="topbar d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <button type="button" class="icon-btn d-xl-none" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open navigation menu">
            <i class="bi bi-list" style="font-size:20px" aria-hidden="true"></i>
        </button>
        <div class="breadcrumb-lite">Workspace <span style="color:var(--text-tertiary)">/</span> <strong>Restock orders</strong></div>
    </div>
    <div class="d-flex align-items-center gap-2 gap-md-3">

        <div class="search-wrap">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="ordersSearch" class="search-input" placeholder="Search orders..." aria-label="Search orders" autocomplete="off">
            <kbd>⌘K</kbd>
        </div>

        <div class="dropdown-app">
            <button type="button" class="icon-btn" id="notificationsBtn" data-dropdown-toggle="notificationsMenu" aria-label="Notifications" aria-haspopup="menu" aria-expanded="false">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="notif-dot" id="notifDot" aria-hidden="true"></span>
            </button>
            <div class="dropdown-menu-app notif-menu" id="notificationsMenu" role="menu">
                <div class="dropdown-label-app">Notifications</div>
                <div class="notif-item" data-notif>
                    <span class="notif-icon is-warning"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                    <div class="notif-body">
                        <p class="notif-title"><?= number_format($pendingOrders) ?> restock order<?= $pendingOrders === 1 ? '' : 's' ?> pending</p>
                        <p class="notif-text">Review and place them with suppliers.</p>
                        <p class="notif-time">Just now</p>
                    </div>
                </div>
                <div class="notif-item" data-notif>
                    <span class="notif-icon is-success"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                    <div class="notif-body">
                        <p class="notif-title"><?= number_format($receivedOrders) ?> order<?= $receivedOrders === 1 ? '' : 's' ?> received</p>
                        <p class="notif-text">Items added to inventory.</p>
                        <p class="notif-time">Recently</p>
                    </div>
                </div>
                <div class="dropdown-divider-app"></div>
                <button type="button" class="dropdown-item-app" id="markAllRead">
                    <i class="bi bi-check2-all" aria-hidden="true"></i>Mark all as read
                </button>
            </div>
        </div>

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

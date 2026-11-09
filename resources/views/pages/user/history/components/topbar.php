<!-- ==========================================================
     COMPONENT: Topbar
     ========================================================== -->
<header class="topbar d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <button type="button" class="icon-btn d-xl-none" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open navigation menu">
            <i class="bi bi-list" style="font-size:20px" aria-hidden="true"></i>
        </button>
        <div class="breadcrumb-lite">Workspace <span style="color:var(--text-tertiary)">/</span> <strong>Order history</strong></div>
    </div>
    <div class="d-flex align-items-center gap-2 gap-md-3">

        <div class="search-wrap">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="topSearch" class="search-input" placeholder="Search history..." aria-label="Search order history" autocomplete="off">
            <kbd>⌘K</kbd>
        </div>

        <!-- Notifications — Bootstrap dropdown -->
        <div class="dropdown">
            <button type="button" class="icon-btn" id="notificationsBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notifications">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="notif-dot" id="notifDot" aria-hidden="true"></span>
            </button>
            <div class="dropdown-menu" aria-labelledby="notificationsBtn" style="min-width:300px;padding:8px">
                <div class="dropdown-label-app">Notifications</div>
                <div class="notif-item" data-notif>
                    <span class="notif-icon is-success"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                    <div class="notif-body">
                        <p class="notif-title"><?= number_format($completedCount) ?> order<?= $completedCount === 1 ? '' : 's' ?> completed</p>
                        <p class="notif-text">All items were received and recorded.</p>
                        <p class="notif-time">Recently</p>
                    </div>
                </div>
                <div class="notif-item" data-notif>
                    <span class="notif-icon is-warning"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
                    <div class="notif-body">
                        <p class="notif-title"><?= number_format($updatedCount) ?> order<?= $updatedCount === 1 ? '' : 's' ?> updated</p>
                        <p class="notif-text">Check the latest changes in the audit trail.</p>
                        <p class="notif-time">Just now</p>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <button type="button" class="dropdown-item" id="markAllRead">
                    <i class="bi bi-check2-all" aria-hidden="true"></i>Mark all as read
                </button>
            </div>
        </div>

        <!-- Account — Bootstrap dropdown -->
        <div class="dropdown">
            <button type="button" class="avatar-btn" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                <span class="avatar" role="img" aria-label="Administrator">AD</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuBtn">
                <li><div class="dropdown-label-app">Administrator</div></li>
                <li><button type="button" class="dropdown-item" data-user-action="profile"><i class="bi bi-person" aria-hidden="true"></i>Profile</button></li>
                <li><button type="button" class="dropdown-item" data-user-action="settings"><i class="bi bi-gear" aria-hidden="true"></i>Settings</button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button type="button" class="dropdown-item is-danger" data-user-action="logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sign out</button></li>
            </ul>
        </div>
    </div>
</header>

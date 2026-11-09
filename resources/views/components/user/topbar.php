<?php 
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');  ?>

<header class="topbar d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <button type="button" class="icon-btn d-xl-none" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open navigation menu">
            <i class="bi bi-list" style="font-size:20px" aria-hidden="true"></i>
        </button>
        <div class="breadcrumb-lite">Workspace <span style="color:var(--text-tertiary)">/</span> <strong>Dashboard</strong></div>
    </div>
    <div class="d-flex align-items-center gap-2 gap-md-3">

        <!-- Search input (wired to table filter via #activitySearch) -->
        <div class="search-wrap">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="activitySearch" class="search-input" placeholder="Search inventory..." aria-label="Search inventory activity" autocomplete="off">
            <kbd>⌘K</kbd>
        </div>

        <!-- Notifications dropdown -->
        <div class="dropdown-app">
            <button type="button" class="icon-btn" id="notificationsBtn" data-dropdown-toggle="notificationsMenu" aria-label="Notifications" aria-haspopup="menu" aria-expanded="false">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="notif-dot" id="notifDot" aria-hidden="true" style="display: none;"></span>
            </button>
            <div class="dropdown-menu-app notif-menu" id="notificationsMenu" role="menu">
                <div class="dropdown-label-app">Notifications</div>
                <?php if (!empty($lowStockProducts)): ?>
                    <?php foreach (array_slice($lowStockProducts, 0, 3) as $p): ?>
                        <div class="notif-item" data-notif>
                            <span class="notif-icon is-warning"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                            <div class="notif-body">
                                <p class="notif-title"><?= $e($p['name']) ?> is low on stock</p>
                                <p class="notif-text">Only <?= (int)($p['quantity'] ?? 0) ?> units remaining.</p>
                                <p class="notif-time">Just now</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="notif-item" style="opacity: 0.6;">
                        <div class="notif-body">
                            <p class="notif-title">All stock levels OK</p>
                            <p class="notif-text">No low stock alerts at this time.</p>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="dropdown-divider-app"></div>
                <button type="button" class="dropdown-item-app" id="markAllRead">
                    <i class="bi bi-check2-all" aria-hidden="true"></i>Mark all as read
                </button>
            </div>
        </div>

        <!-- User menu dropdown on the avatar -->
        <div class="dropdown-app">
            <button type="button" class="avatar-btn" id="userMenuBtn" data-dropdown-toggle="userMenu" aria-label="Account menu" aria-haspopup="menu" aria-expanded="false">
                <span class="avatar" role="img" aria-label="User"><?= $e(strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))) ?></span>
            </button>
            <div class="dropdown-menu-app" id="userMenu" role="menu">
                <div class="dropdown-label-app"><?= $e($_SESSION['username'] ?? 'User') ?></div>
                <button type="button" class="dropdown-item-app" data-user-action="profile">
                    <i class="bi bi-person" aria-hidden="true"></i>Profile
                </button>
                <button type="button" class="dropdown-item-app" data-user-action="settings">
                    <i class="bi bi-gear" aria-hidden="true"></i>Settings
                </button>
                <button type="button" class="dropdown-item-app" data-user-action="help">
                    <i class="bi bi-question-circle" aria-hidden="true"></i>Help & support
                </button>
                <div class="dropdown-divider-app"></div>
                <form method="post" action="/logout" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $e($authService->getCsrfToken() ?? '') ?>">
                    <button type="submit" class="dropdown-item-app is-danger" data-user-action="logout">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
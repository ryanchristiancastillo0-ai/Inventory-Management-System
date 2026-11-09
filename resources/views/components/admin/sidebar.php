<?php
/**
 * ADMIN SIDEBAR COMPONENT
 * Rendered by app.php. Uses main.css tokens/classes 1:1 with the design reference.
 *
 * Desktop : .dashboard-sidebar.d-none.d-xl-block   (visible ≥ 1200px)
 * Mobile  : .offcanvas.mobile-sidebar#mobileSidebar (opened by topbar button)
 *
 * Optional vars:
 *   @var int|string|null $sidebarProductsCount
 *   @var int|string|null $sidebarInventoryCount
 *   @var string          $topbarUserName / $topbarUserRole / $topbarUserInitials
 */

/* ---------- Safe defaults ---------- */
$sidebarProductsCount  = $sidebarProductsCount  ?? null;
$sidebarInventoryCount = $sidebarInventoryCount ?? null;
$sidebarUserName       = $topbarUserName        ?? 'Administrator';
$sidebarUserRole       = $topbarUserRole        ?? 'Admin Account';
$sidebarInitials       = $topbarUserInitials    ?? 'AD';

/* Sign-out CSRF (same session key AuthService uses) */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$sidebarLogoutCsrf = $_SESSION['csrf_token'];

/* ---------- Helpers ---------- */
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$currentPath = '/' . ltrim($currentPath, '/');

$isActive = static function (array $paths) use ($currentPath): bool {
    foreach ($paths as $p) {
        $p = '/' . ltrim($p, '/');
        if ($p === '/' && $currentPath === '/') return true;
        if ($p !== '/' && ($currentPath === $p || strpos($currentPath, $p . '/') === 0)) return true;
    }
    return false;
};

/* ---------- Nav manifest (groups mirror the reference) ---------- */
$navGroups = [
    [
        'label' => 'Workspace',
        'items' => [
            ['href' => '/admin/dashboard',     'icon' => 'bi-grid-1x2', 'label' => 'Dashboard',       'match' => ['/admin/dashboard'],     'badge' => null],
            ['href' => '/admin/users',         'icon' => 'bi-box-seam', 'label' => 'User Management', 'match' => ['/admin/users'],         'badge' => $sidebarProductsCount],
            ['href' => '/admin/activity-logs', 'icon' => 'bi-tags',     'label' => 'Activity Logs',   'match' => ['/admin/activity-logs'], 'badge' => $sidebarInventoryCount],
            ['href' => '/admin/reports',       'icon' => 'bi-journal',  'label' => 'Reports',         'match' => ['/admin/reports'],       'badge' => null],
            ['href' => '/settings',            'icon' => 'bi-gear-fill','label' => 'Settings',        'match' => ['/settings'],            'badge' => null],
          
        ],
    ],
   
];

/* ---------- Shared renderer (desktop + mobile) ---------- */
$renderNav = static function (array $groups, callable $isActive, callable $e) {
    foreach ($groups as $group) {
        ?>
        <div class="nav-group-label"><?= $e($group['label']) ?></div>
        <ul class="nav-list">
            <?php foreach ($group['items'] as $item):
                $active = $isActive($item['match']);
                $badge  = ($item['badge'] !== null && $item['badge'] !== '')
                    ? (string) $item['badge']
                    : '';
            ?>
                <li>
                    <a class="nav-link-item<?= $active ? ' active' : '' ?>"
                       href="<?= $e($item['href']) ?>"
                       <?= $active ? 'aria-current="page"' : '' ?>>
                        <i class="bi <?= $e($item['icon']) ?>" aria-hidden="true"></i>
                        <span><?= $e($item['label']) ?></span>
                        <?php if ($badge !== ''): ?>
                            <span class="nav-badge"><?= $e($badge) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }
};
?>

<!-- Tiny mobile-only overrides so .sidebar-inner lays out inside offcanvas-body -->
<style>
    .offcanvas.mobile-sidebar .offcanvas-body {
        padding: 0;
        display: flex;
        flex-direction: column;
    }
    .offcanvas.mobile-sidebar .sidebar-inner {
        padding: 16px 12px;
        flex: 1;
    }
    .offcanvas.mobile-sidebar .brand {
        margin-bottom: 0;
    }
</style>


<!-- ============ DESKTOP SIDEBAR ============ -->
<aside class="dashboard-sidebar d-none d-xl-block" aria-label="Primary">
    <div class="sidebar-inner">
        <a href="/admin/dashboard" class="brand">
            <span class="brand-mark"><i class="bi bi-box-seam-fill" aria-hidden="true"></i></span>
            <span class="brand-text">STOKLIVO</span>
        </a>

        <nav aria-label="Main navigation">
            <?php $renderNav($navGroups, $isActive, $e); ?>
        </nav>

        <div class="sidebar-footer">
            <span class="avatar" aria-hidden="true"><?= $e($sidebarInitials) ?></span>
            <div class="profile-text">
                <div class="profile-name"><?= $e($sidebarUserName) ?></div>
                <div class="profile-role"><?= $e($sidebarUserRole) ?></div>
            </div>
            <form method="post" action="/logout" style="display:inline-flex;">
                <input type="hidden" name="csrf_token" value="<?= $e($sidebarLogoutCsrf) ?>">
                <button type="submit" class="icon-btn" aria-label="Sign out" title="Sign out">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</aside>


<!-- ============ MOBILE SIDEBAR (OFFCANVAS) ============ -->
<div class="offcanvas offcanvas-start mobile-sidebar" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header">
        <a href="/admin/dashboard" class="brand">
            <span class="brand-mark"><i class="bi bi-box-seam-fill" aria-hidden="true"></i></span>
            <span class="brand-text" id="mobileSidebarLabel">Inventory.</span>
        </a>
        <button type="button" class="icon-btn" data-bs-dismiss="offcanvas" aria-label="Close navigation">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>
    <div class="offcanvas-body">
        <div class="sidebar-inner">
            <nav aria-label="Main navigation">
                <?php $renderNav($navGroups, $isActive, $e); ?>
            </nav>
            <div class="sidebar-footer">
                <span class="avatar" aria-hidden="true"><?= $e($sidebarInitials) ?></span>
                <div class="profile-text">
                    <div class="profile-name"><?= $e($sidebarUserName) ?></div>
                    <div class="profile-role"><?= $e($sidebarUserRole) ?></div>
                </div>
                <form method="post" action="/logout" style="display:inline-flex;">
                    <input type="hidden" name="csrf_token" value="<?= $e($sidebarLogoutCsrf) ?>">
                    <button type="submit" class="icon-btn" aria-label="Sign out" title="Sign out">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
(function () {
    'use strict';

    /* Auto-close mobile sidebar after a nav link or brand click */
    var oc = document.getElementById('mobileSidebar');
    if (oc) {
        /* Keep the page width steady while the drawer is open. Bootstrap
           hides the scrollbar and pads <body>; the mobile-nav-open class
           reserves the gutter instead so content doesn't squeeze. */
        oc.addEventListener('show.bs.offcanvas', function () {
            if (window.innerWidth > document.documentElement.clientWidth) {
                document.documentElement.classList.add('mobile-nav-open');
            }
        });
        oc.addEventListener('hidden.bs.offcanvas', function () {
            document.documentElement.classList.remove('mobile-nav-open');
        });

        oc.addEventListener('click', function (e) {
            if (!e.target.closest('a.nav-link-item') && !e.target.closest('a.brand')) return;
            if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                var inst = bootstrap.Offcanvas.getInstance(oc);
                if (inst) inst.hide();
            }
        });
    }

    /* Sign out also posts the real /logout form (in the sidebar footer) */
})();
</script>

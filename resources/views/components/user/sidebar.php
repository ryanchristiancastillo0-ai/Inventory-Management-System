<?php
/** @var int $totalProducts */
$totalProducts = $totalProducts ?? 0;
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

/* Sign-out CSRF (same session key AuthService uses) */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$sidebarLogoutCsrf = $_SESSION['csrf_token'];
?>
<aside class="dashboard-sidebar d-none d-xl-block" aria-label="Primary">
    <div class="sidebar-inner">
        <a href="/dashboard" class="brand">
            <span class="brand-mark"><i class="bi bi-box-seam-fill" aria-hidden="true"></i></span>
            <span class="brand-text">Inventory.</span>
        </a>

        <nav aria-label="Main navigation">
            <div class="nav-group-label">Workspace</div>
            <ul class="nav-list">
                <li><a href="/dashboard" class="nav-link-item" aria-current="page"><i class="bi bi-grid-1x2" aria-hidden="true"></i>Dashboard</a></li>
                <li><a href="/products" class="nav-link-item"><i class="bi bi-box-seam" aria-hidden="true"></i>Products<?php if ($totalProducts > 0): ?><span class="nav-badge" id="sidebarProductsCount"><?= number_format($totalProducts) ?></span><?php endif; ?></a></li>
                <li><a href="/inventory" class="nav-link-item"><i class="bi bi-tags" aria-hidden="true"></i>Inventory</a></li>
                <li><a href="/orders" class="nav-link-item"><i class="bi bi-list-ol" aria-hidden="true"></i>Orders</a></li>
                <li><a href="/stock-movements" class="nav-link-item"><i class="bi bi-arrow-left-right" aria-hidden="true"></i>Stock Movements</a></li>
                <li><a href="/order-history" class="nav-link-item"><i class="bi bi-clock-history" aria-hidden="true"></i>History</a></li>
            </ul>

            <div class="nav-group-label">Settings</div>
            <ul class="nav-list">
                <li><a href="/profile" class="nav-link-item<?= (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) === '/profile') ? ' active' : '' ?>"><i class="bi bi-person" aria-hidden="true"></i>My profile</a></li>
                <li><a href="/settings" class="nav-link-item"><i class="bi bi-gear" aria-hidden="true"></i>Settings</a></li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <span class="avatar" aria-hidden="true"><?= $e(strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))) ?></span>
            <div class="profile-text">
                <div class="profile-name"><?= $e($_SESSION['username'] ?? 'User') ?></div>
                <div class="profile-role"><?= $e(strtoupper($_SESSION['role'] ?? 'USER')) ?> Account</div>
            </div>
            <form method="post" action="/logout">
                <input type="hidden" name="csrf_token" value="<?= $e($sidebarLogoutCsrf) ?>">
                <button type="submit" class="icon-btn" aria-label="Sign out" title="Sign out" style="display:inline-flex;">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

<?php
/**
 * ADMIN TOPBAR COMPONENT
 * Rendered by app.php (above $pageFile). Uses main.css classes 1:1 with the
 * design reference, but relies on Bootstrap's data-bs-toggle="dropdown" for
 * reliable open/close/positioning (no custom positioning JS).
 *
 * Optional variables (sensible defaults if omitted):
 *   @var string $topbarLeaf              Breadcrumb leaf (auto from URI)
 *   @var string $topbarSection           Breadcrumb section (default "Workspace")
 *   @var string $topbarSearchPlaceholder
 *   @var bool   $topbarShowSearch
 *   @var array  $topbarNotifItems        [{icon,tone,title,text,time}, ...]
 *   @var string $topbarUserName / $topbarUserRole / $topbarUserInitials
 */

$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

/* Session-ready CSRF token for the sign-out form (same key AuthService uses) */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$logoutCsrf = $_SESSION['csrf_token'];

/* Auto breadcrumb from URI (skips /admin prefix) */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$segs = array_values(array_filter(explode('/', trim($path, '/'))));
if (!empty($segs) && strtolower($segs[0]) === 'admin') {
    array_shift($segs);
}
$autoLeaf = !empty($segs) ? ucwords(str_replace('-', ' ', end($segs))) : 'Dashboard';

$topbarSection           = $topbarSection           ?? 'Workspace';
$topbarLeaf              = $topbarLeaf              ?? $autoLeaf;
$topbarShowSearch        = $topbarShowSearch        ?? true;
$topbarSearchPlaceholder = $topbarSearchPlaceholder ?? 'Search…';
$topbarUserName          = $topbarUserName          ?? 'Administrator';
$topbarUserRole          = $topbarUserRole          ?? 'Admin Account';
$topbarUserInitials      = $topbarUserInitials      ?? 'AD';
$topbarNotifItems        = $topbarNotifItems        ?? [
    ['icon' => 'bi-exclamation-triangle', 'tone' => 'warning', 'title' => 'Welcome back', 'text' => 'Your dashboard is up to date.', 'time' => 'Just now'],
    ['icon' => 'bi-check2-circle',        'tone' => 'success', 'title' => 'All systems normal', 'text' => 'No issues require attention.', 'time' => 'Recently'],
];
?>

<style>
    /* ==========================================================
       Topbar dropdown theme overrides
       Bootstrap provides positioning + show/hide; these variables
       align the visual with .dropdown-menu-app from main.css.
       ========================================================== */
    .topbar .dropdown-menu {
        --bs-dropdown-bg: var(--surface);
        --bs-dropdown-color: var(--text);
        --bs-dropdown-border-color: var(--border);
        --bs-dropdown-border-radius: var(--radius);
        --bs-dropdown-border-width: 1px;
        --bs-dropdown-padding-x: 6px;
        --bs-dropdown-padding-y: 6px;
        --bs-dropdown-min-width: 200px;
        --bs-dropdown-font-size: 13px;
        --bs-dropdown-spacer: 6px;
        --bs-dropdown-divider-bg: var(--border);
        --bs-dropdown-link-color: var(--text);
        --bs-dropdown-link-hover-color: var(--text);
        --bs-dropdown-link-hover-bg: var(--surface-hover);
        --bs-dropdown-link-active-color: var(--text);
        --bs-dropdown-link-active-bg: var(--surface-hover);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
    }
    /* Notification menu keeps its own min-width from main.css (.notif-menu) */

    /* Toast fallback (page may override window.showToast) */
    .toast-stack {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 2000;
        display: flex;
        flex-direction: column;
        gap: 8px;
        pointer-events: none;
    }
    .toast-app {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: var(--accent);
        color: var(--accent-contrast);
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 500;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        pointer-events: auto;
        animation: topbarToastIn 200ms ease both;
        max-width: 340px;
    }
    .toast-app i { font-size: 14px; flex-shrink: 0; }
    .toast-app.toast-out { animation: topbarToastOut 200ms ease both; }
    @keyframes topbarToastIn  { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes topbarToastOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(8px); } }
</style>


<header class="topbar d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <!-- Hamburger — visible below xl (matches sidebar's d-none d-xl-block) -->
        <button type="button" class="icon-btn d-xl-none"
                data-bs-toggle="offcanvas"
                data-bs-target="#mobileSidebar"
                aria-controls="mobileSidebar"
                aria-label="Open navigation menu">
            <i class="bi bi-list" style="font-size:20px" aria-hidden="true"></i>
        </button>
        <div class="breadcrumb-lite">
            <?= $e($topbarSection) ?>
            <span style="color:var(--text-tertiary)">/</span>
            <strong><?= $e($topbarLeaf) ?></strong>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 gap-md-3">
        <?php if ($topbarShowSearch): ?>
            <div class="search-wrap">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search"
                       id="topbarSearch"
                       class="search-input"
                       placeholder="<?= $e($topbarSearchPlaceholder) ?>"
                       aria-label="Search"
                       autocomplete="off">
                <kbd>⌘K</kbd>
            </div>
        <?php endif; ?>

        <!-- ============ NOTIFICATIONS ============ -->
        <div class="dropdown">
            <button type="button"
                    class="icon-btn"
                    id="notificationsBtn"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    aria-label="Notifications">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="notif-dot" id="notifDot" aria-hidden="true"></span>
            </button>

            <div class="dropdown-menu dropdown-menu-end notif-menu" aria-labelledby="notificationsBtn">
                <div class="dropdown-label-app">Notifications</div>

                <?php foreach ($topbarNotifItems as $n):
                    $tone    = (string) ($n['tone'] ?? '');
                    $toneCls = $tone !== '' ? ' is-' . $e($tone) : '';
                ?>
                    <div class="notif-item" data-notif>
                        <span class="notif-icon<?= $toneCls ?>">
                            <i class="bi <?= $e($n['icon'] ?? 'bi-bell') ?>" aria-hidden="true"></i>
                        </span>
                        <div class="notif-body">
                            <p class="notif-title"><?= $e($n['title'] ?? '') ?></p>
                            <?php if (!empty($n['text'])): ?>
                                <p class="notif-text"><?= $e($n['text']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($n['time'])): ?>
                                <p class="notif-time"><?= $e($n['time']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="dropdown-divider-app"></div>
                <button type="button" class="dropdown-item-app" id="markAllRead">
                    <i class="bi bi-check2-all" aria-hidden="true"></i>
                    Mark all as read
                </button>
            </div>
        </div>

        <!-- ============ USER MENU ============ -->
        <div class="dropdown">
            <button type="button"
                    class="avatar-btn"
                    id="userMenuBtn"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Account menu">
                <span class="avatar" role="img" aria-label="<?= $e($topbarUserRole) ?>">
                    <?= $e($topbarUserInitials) ?>
                </span>
            </button>

            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuBtn">
                <div class="dropdown-label-app"><?= $e($topbarUserName) ?></div>

                <a href="/profile" class="dropdown-item-app">
                    <i class="bi bi-person" aria-hidden="true"></i>Profile
                </a>
                <button type="button" class="dropdown-item-app" data-user-action="settings">
                    <i class="bi bi-gear" aria-hidden="true"></i>Settings
                </button>

                <div class="dropdown-divider-app"></div>

                <form method="post" action="/logout" style="display:contents">
                    <input type="hidden" name="csrf_token" value="<?= $e($logoutCsrf) ?>">
                    <button type="submit" class="dropdown-item-app is-danger">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sign out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>


<script>
(function () {
    'use strict';

    /* =========================================================
       TOAST FALLBACK — pages may override window.showToast
       ========================================================= */
    if (typeof window.showToast !== 'function') {
        window.showToast = function (message, icon) {
            var stack = document.getElementById('toastStack');
            if (!stack) {
                stack = document.createElement('div');
                stack.id = 'toastStack';
                stack.className = 'toast-stack';
                stack.setAttribute('aria-live', 'polite');
                stack.setAttribute('aria-atomic', 'true');
                document.body.appendChild(stack);
            }
            var t = document.createElement('div');
            t.className = 'toast-app';
            t.innerHTML = '<i class="bi ' + (icon || 'bi-check2-circle') + '" aria-hidden="true"></i><span></span>';
            t.querySelector('span').textContent = message;
            stack.appendChild(t);
            setTimeout(function () {
                t.classList.add('toast-out');
                t.addEventListener('animationend', function () { t.remove(); }, { once: true });
            }, 3200);
        };
    }

    /* =========================================================
       TOPBAR SEARCH — dispatch 'topbar:search' for pages to listen
       ========================================================= */
    var input = document.getElementById('topbarSearch');
    if (input) {
        input.addEventListener('input', function () {
            document.dispatchEvent(new CustomEvent('topbar:search', {
                detail: { value: input.value }
            }));
        });
    }

    /* =========================================================
       ⌘K / Ctrl+K — focus topbar search
       ========================================================= */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && String(e.key).toLowerCase() === 'k') {
            e.preventDefault();
            if (input) input.focus();
        }
    });

    /* =========================================================
       NOTIFICATION ITEM CLICK — toast only (menu stays open
       because data-bs-auto-close="outside")
       ========================================================= */
    document.querySelectorAll('[data-notif]').forEach(function (item) {
        item.addEventListener('click', function () {
            var t = item.querySelector('.notif-title');
            window.showToast(t ? t.textContent : 'Notification', 'bi-bell');
        });
    });

    /* =========================================================
       MARK ALL AS READ
       ========================================================= */
    var markAll = document.getElementById('markAllRead');
    if (markAll) {
        markAll.addEventListener('click', function () {
            var dot = document.getElementById('notifDot');
            if (dot) dot.style.display = 'none';
            window.showToast('All notifications marked as read', 'bi-check2-all');
        });
    }

    /* =========================================================
       USER MENU ACTIONS
       ========================================================= */
    document.querySelectorAll('.dropdown-menu [data-user-action]').forEach(function (item) {
        item.addEventListener('click', function () {
            var action = item.getAttribute('data-user-action');
            if (action === 'settings') {
                window.location.href = '/settings';
            } else {
                window.showToast(
                    'Opening ' + item.textContent.trim().toLowerCase() + '…',
                    'bi-arrow-right'
                );
            }
        });
    });
})();
</script>

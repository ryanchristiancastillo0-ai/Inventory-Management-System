<?php
/**
 * ORDER HISTORY PAGE
 * Rendered inside app.php. Sidebar and shell come from the layout.
 *
 * Expected variables (all optional — falls back to empty defaults):
 *   @var array       $history
 *   @var int         $totalTransactions, $createdCount, $updatedCount,
 *                    $completedCount, $cancelledCount
 *   @var string      $actionFilter        CREATED|UPDATED|COMPLETED|CANCELLED|''
 *   @var int|null    $selectedOrderId
 *   @var string      $csrfToken
 */

$history           = $history           ?? [];
$totalTransactions = $totalTransactions ?? count($history);
$createdCount      = $createdCount      ?? 0;
$updatedCount      = $updatedCount      ?? 0;
$completedCount    = $completedCount    ?? 0;
$cancelledCount    = $cancelledCount    ?? 0;
$actionFilter      = strtoupper((string) ($actionFilter ?? ''));
$selectedOrderId   = isset($selectedOrderId) && $selectedOrderId !== null ? (int) $selectedOrderId : null;
$csrfToken         = $csrfToken         ?? '';

/* PHP 5.4+ safe helpers */
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$pick = static function (array $rec, array $keys, string $default = '') {
    foreach ($keys as $k) {
        if (isset($rec[$k]) && $rec[$k] !== '' && $rec[$k] !== null) return (string) $rec[$k];
    }
    return $default;
};

/* Action registry — label, colour class, icon */
$actionMeta = static function (string $a) {
    switch (strtoupper($a)) {
        case 'CREATED':   return ['label' => 'Created',   'cls' => 'is-created',   'icon' => 'bi-plus-circle'];
        case 'UPDATED':   return ['label' => 'Updated',   'cls' => 'is-updated',   'icon' => 'bi-pencil-square'];
        case 'COMPLETED': return ['label' => 'Completed', 'cls' => 'is-completed', 'icon' => 'bi-check2-circle'];
        case 'CANCELLED': return ['label' => 'Cancelled', 'cls' => 'is-cancelled', 'icon' => 'bi-x-circle'];
        default:          return ['label' => $a ?: 'Unknown', 'cls' => 'is-default', 'icon' => 'bi-dot'];
    }
};

/* Status pill for "old → new" transitions */
$statusCls = static function (string $s) {
    switch (strtoupper($s)) {
        case 'PENDING':   return 'status-warning';
        case 'ORDERED':   return 'status-info';
        case 'RECEIVED':
        case 'COMPLETED': return 'status-success';
        case 'CANCELLED': return 'status-danger';
        default:          return 'status-info';
    }
};

$fmtWhen = static function (string $ts) {
    if ($ts === '') return '—';
    $t = strtotime($ts);
    return $t === false ? $ts : date('M j, Y · g:i A', $t);
};

/* Build URL helper that preserves the current action filter & order filter */
$buildUrl = static function (array $overrides = []) use ($actionFilter, $selectedOrderId) {
    $params = [];
    if ($actionFilter !== '')       $params['action']   = $actionFilter;
    if ($selectedOrderId !== null)  $params['order_id'] = $selectedOrderId;

    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $qs = http_build_query($params);
    return '/order-history' . ($qs !== '' ? '?' . $qs : '');
};

$actionFilterLabel = $actionFilter !== '' ? $actionMeta($actionFilter)['label'] : 'All actions';
$hasServerFilter   = ($actionFilter !== '' || $selectedOrderId !== null);

/* Flash */
$flashSuccess = $_SESSION['success'] ?? null;
$flashError   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>


<style>
    /* ==========================================================
       ORDER HISTORY — page-specific styles
       ========================================================== */

    /* ---------- Page header ---------- */
    .page-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
    }
    .page-header .page-title { margin: 0; }
    .page-header .page-subtitle { margin: 6px 0 0; display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }

    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px 3px 10px;
        border-radius: 999px;
        background: var(--surface-muted);
        border: 1px solid var(--border);
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: background-color 120ms ease, border-color 120ms ease, color 120ms ease;
    }
    .filter-chip:hover {
        background: var(--surface-hover);
        border-color: var(--border-strong);
        color: var(--text);
    }
    .filter-chip i { font-size: 11px; }
    .filter-chip .chip-x {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px; height: 16px;
        border-radius: 50%;
        background: transparent;
        border: 0;
        color: var(--text-tertiary);
        padding: 0;
        margin-left: 2px;
    }
    .filter-chip .chip-x:hover { background: var(--danger-bg); color: var(--danger); }

    /* ---------- Stats strip ---------- */
    .stats-strip {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-top: 24px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .stat-item {
        padding: 20px 22px;
        border-right: 1px solid var(--border);
        min-width: 0;
        position: relative;
        transition: background-color 140ms ease;
    }
    .stat-item:last-child { border-right: 0; }
    .stat-item:hover { background: var(--surface-muted); }

    .stat-top {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
    }
    .stat-dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: var(--text-tertiary);
        flex-shrink: 0;
    }
    .stat-dot.is-created   { background: var(--info); }
    .stat-dot.is-updated   { background: var(--warning); }
    .stat-dot.is-completed { background: var(--success); }
    .stat-dot.is-cancelled { background: var(--danger); }

    .stat-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-secondary);
        margin: 0;
    }
    .stat-value {
        font-size: 24px;
        font-weight: 600;
        letter-spacing: -0.6px;
        line-height: 1.15;
        margin: 0;
        color: var(--text);
        font-variant-numeric: tabular-nums;
    }
    .stat-value.is-created   { color: var(--info); }
    .stat-value.is-updated   { color: var(--warning); }
    .stat-value.is-completed { color: var(--success); }
    .stat-value.is-cancelled { color: var(--danger); }

    @media (max-width: 991.98px) {
        .stats-strip { grid-template-columns: repeat(3, 1fr); }
        .stat-item:nth-child(3n) { border-right: 0; }
        .stat-item:nth-child(n+4) { border-top: 1px solid var(--border); }
    }
    @media (max-width: 575.98px) {
        .stats-strip { grid-template-columns: repeat(2, 1fr); }
        .stat-item:nth-child(3n) { border-right: 1px solid var(--border); }
        .stat-item:nth-child(2n) { border-right: 0; }
        .stat-item:nth-child(n+3) { border-top: 1px solid var(--border); }
    }

    /* ---------- Filter bar ---------- */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 24px;
    }
    .filter-bar__group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .filter-search { width: 300px; }
    .filter-count {
        font-size: 12px;
        color: var(--text-tertiary);
        font-variant-numeric: tabular-nums;
    }
    @media (max-width: 767.98px) { .filter-search { width: 100%; } }

    /* ---------- Bootstrap dropdown (theme overrides) ---------- */
    .dropdown-menu {
        --bs-dropdown-bg: var(--surface);
        --bs-dropdown-border-color: var(--border);
        --bs-dropdown-link-color: var(--text);
        --bs-dropdown-link-hover-bg: var(--surface-muted);
        --bs-dropdown-link-hover-color: var(--text);
        --bs-dropdown-link-active-bg: var(--surface-hover);
        --bs-dropdown-link-active-color: var(--text);
        border-radius: 10px;
        box-shadow: 0 12px 32px -8px rgba(0,0,0,0.18), 0 4px 12px -4px rgba(0,0,0,0.08);
        padding: 6px;
        font-size: 13px;
        min-width: 200px;
        margin-top: 4px !important;
    }
    .dropdown-item {
        border-radius: 6px;
        padding: 8px 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 500;
    }
    .dropdown-item i { font-size: 13px; color: var(--text-secondary); }
    .dropdown-item:hover i,
    .dropdown-item:focus i { color: var(--text); }
    .dropdown-item.active,
    .dropdown-item:active {
        background: var(--surface-hover);
        color: var(--text);
    }
    .dropdown-item.active i { color: var(--text); }
    .dropdown-item.is-danger { color: var(--danger); }
    .dropdown-item.is-danger i { color: var(--danger); }
    .dropdown-item.is-danger:hover,
    .dropdown-item.is-danger:focus { background: var(--danger-bg); color: var(--danger); }
    .dropdown-divider {
        margin: 4px 0;
        border-color: var(--border);
        opacity: 1;
    }
    .dropdown-label-app {
        padding: 6px 10px 4px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-tertiary);
    }
    .dropdown-check {
        margin-left: auto;
        font-size: 11px;
        color: var(--success);
    }

    /* ---------- Action badge ---------- */
    .action-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        letter-spacing: 0.01em;
    }
    .action-badge i { font-size: 12px; }
    .action-badge.is-created   { background: var(--info-bg);    color: var(--info); }
    .action-badge.is-updated   { background: var(--warning-bg); color: var(--warning); }
    .action-badge.is-completed { background: var(--success-bg); color: var(--success); }
    .action-badge.is-cancelled { background: var(--danger-bg);  color: var(--danger); }
    .action-badge.is-default   { background: var(--surface-muted); color: var(--text-secondary); }

    /* ---------- Table cells ---------- */
    .history-row { cursor: pointer; transition: background-color 120ms ease; }
    .history-row > td { transition: box-shadow 120ms ease; }
    .history-row:hover { background: var(--surface-muted); }
    .history-row:hover > td:first-child { box-shadow: inset 3px 0 0 var(--text); }

    .order-ref {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 6px;
        background: var(--surface-muted);
        border: 1px solid var(--border);
        color: var(--text);
        font-size: 12px;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
        text-decoration: none;
        white-space: nowrap;
        transition: background-color 120ms ease, border-color 120ms ease, transform 120ms ease;
    }
    .order-ref:hover {
        background: var(--surface-hover);
        border-color: var(--border-strong);
        color: var(--text);
        transform: translateY(-1px);
    }

    .cell-details {
        font-size: 13px;
        color: var(--text-secondary);
        max-width: 320px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cell-by {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--text-secondary);
        white-space: nowrap;
    }
    .cell-by .mini-avatar {
        width: 20px; height: 20px;
        border-radius: 50%;
        background: var(--surface-hover);
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .cell-when {
        font-size: 12px;
        color: var(--text-secondary);
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .cell-actions { width: 44px; text-align: right; }
    .row-actions-btn { width: 28px; height: 28px; font-size: 14px; }

    .status-flow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        white-space: nowrap;
        color: var(--text-tertiary);
    }
    .status-flow i { font-size: 10px; }

    /* ---------- Empty state ---------- */
    .empty-state { padding: 80px 24px; text-align: center; }
    .empty-icon {
        width: 64px; height: 64px;
        border-radius: 16px;
        background: var(--surface-hover);
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        margin-bottom: 18px;
    }
    .empty-title { font-size: 16px; font-weight: 600; color: var(--text); margin: 0 0 6px; }
    .empty-text { font-size: 13px; color: var(--text-secondary); margin: 0 0 22px; }

    /* ---------- Toast ---------- */
    .toast-stack {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 1080;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }
    .toast-app {
        pointer-events: auto;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 11px 16px;
        border-radius: 10px;
        background: var(--surface);
        border: 1px solid var(--border);
        box-shadow: 0 12px 32px -8px rgba(0, 0, 0, 0.18);
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
        animation: toastIn 220ms cubic-bezier(0.2, 0.8, 0.2, 1);
        max-width: 320px;
    }
    .toast-app i { color: var(--success); font-size: 15px; flex-shrink: 0; }
    .toast-app.toast-out { animation: toastOut 200ms ease forwards; }
    @keyframes toastIn  { from { opacity: 0; transform: translateY(8px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
    @keyframes toastOut { to   { opacity: 0; transform: translateY(8px) scale(0.98); } }

    /* Dropdown trigger widths */
    #actionFilterBtn { min-width: 170px; justify-content: space-between; }
</style>


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


<main class="dashboard-content">

    <!-- ==========================================================
         COMPONENT: Page header
         ========================================================== -->
    <section class="page-header" aria-labelledby="pageTitle">
        <div class="min-w-0">
            <h1 class="page-title" id="pageTitle">Order history</h1>
            <p class="page-subtitle">
                Full audit trail of every order change
                <?php if ($selectedOrderId !== null): ?>
                    <a class="filter-chip" href="<?= $e($buildUrl(['order_id' => null])) ?>" title="Clear order filter">
                        <i class="bi bi-receipt" aria-hidden="true"></i>
                        Order #<?= (int) $selectedOrderId ?>
                        <span class="chip-x" aria-hidden="true"><i class="bi bi-x" aria-hidden="true"></i></span>
                    </a>
                <?php endif; ?>
                <?php if ($actionFilter !== ''): ?>
                    <a class="filter-chip" href="<?= $e($buildUrl(['action' => null])) ?>" title="Clear action filter">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <?= $e($actionFilterLabel) ?>
                        <span class="chip-x" aria-hidden="true"><i class="bi bi-x" aria-hidden="true"></i></span>
                    </a>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="/orders" class="btn-secondary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>Back to orders
            </a>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Stats strip
         ========================================================== -->
    <section class="stats-strip" aria-label="Order history statistics">
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot"></span>
                <p class="stat-label">Total transactions</p>
            </div>
            <p class="stat-value"><?= number_format($totalTransactions) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-created"></span>
                <p class="stat-label">Created</p>
            </div>
            <p class="stat-value is-created"><?= number_format($createdCount) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-updated"></span>
                <p class="stat-label">Updated</p>
            </div>
            <p class="stat-value is-updated"><?= number_format($updatedCount) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-completed"></span>
                <p class="stat-label">Completed</p>
            </div>
            <p class="stat-value is-completed"><?= number_format($completedCount) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-cancelled"></span>
                <p class="stat-label">Cancelled</p>
            </div>
            <p class="stat-value is-cancelled"><?= number_format($cancelledCount) ?></p>
        </div>
    </section>

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

    <!-- ==========================================================
         COMPONENT: History table
         ========================================================== -->
    <section class="section-gap">
        <div class="panel">

            <?php if (empty($history)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></div>
                    <h3 class="empty-title">No history records<?= $hasServerFilter ? ' matching your filters' : ' yet' ?></h3>
                    <p class="empty-text">
                        <?= $hasServerFilter
                            ? 'Try clearing the filters to see all records.'
                            : 'Order activity will appear here once orders are created, updated, completed or cancelled.' ?>
                    </p>
                    <?php if ($hasServerFilter): ?>
                        <a href="/order-history" class="btn-secondary">
                            <i class="bi bi-x" aria-hidden="true"></i>Clear filters
                        </a>
                    <?php else: ?>
                        <a href="/orders" class="btn-primary-app">
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>Go to orders
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table activity-table align-middle">
                        <caption>Order history: action, order reference, details, performer and timestamp.</caption>
                        <thead>
                            <tr>
                                <th scope="col">When</th>
                                <th scope="col">Action</th>
                                <th scope="col">Order</th>
                                <th scope="col">Details</th>
                                <th scope="col">By</th>
                                <th scope="col" style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody id="historyBody">
                            <?php foreach ($history as $rec):
                                $hid        = (int) ($rec['id'] ?? 0);
                                $orderId    = (int) ($rec['order_id'] ?? 0);
                                $orderNum   = $pick($rec, ['order_number', 'number', 'order_no'], $orderId > 0 ? ('#' . $orderId) : '—');
                                $actionRaw  = (string) ($rec['action'] ?? '');
                                $details    = $pick($rec, ['description', 'details', 'notes', 'remarks', 'message', 'summary'], '');
                                $performedBy = $pick($rec, ['performed_by', 'user_name', 'created_by', 'user', 'actor'], '');
                                $oldStatus  = $pick($rec, ['old_status', 'previous_status', 'from_status'], '');
                                $newStatus  = $pick($rec, ['new_status', 'to_status', 'status'], '');
                                $when       = $pick($rec, ['created_at', 'created_on', 'date', 'created', 'timestamp'], '');

                                $meta = $actionMeta($actionRaw);
                                $performerInitial = $performedBy !== '' ? strtoupper(substr($performedBy, 0, 1)) : '?';
                            ?>
                                <tr class="history-row"
                                    data-history-id="<?= $hid ?>"
                                    data-action="<?= $e(strtoupper($actionRaw)) ?>"
                                    data-order-number="<?= $e($orderNum) ?>"
                                    data-order-id="<?= $orderId ?>"
                                    data-performed-by="<?= $e($performedBy) ?>"
                                    data-details="<?= $e($details) ?>"
                                    onclick="if (!event.target.closest('a,button,.dropdown-menu')) { window.location.href = '/order-history/view?id=<?= $hid ?>'; }"
                                >
                                    <td class="cell-when"><?= $e($fmtWhen($when)) ?></td>
                                    <td>
                                        <span class="action-badge <?= $e($meta['cls']) ?>">
                                            <i class="bi <?= $e($meta['icon']) ?>" aria-hidden="true"></i>
                                            <?= $e($meta['label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($orderId > 0): ?>
                                            <a class="order-ref" href="/orders?id=<?= $orderId ?>">
                                                <i class="bi bi-receipt" aria-hidden="true"></i>
                                                <?= $e($orderNum) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="cell-when">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-details" title="<?= $e($details) ?>">
                                        <?php if ($details !== ''): ?>
                                            <?= $e($details) ?>
                                        <?php elseif ($oldStatus !== '' || $newStatus !== ''): ?>
                                            <span class="status-flow">
                                                <span class="status <?= $e($statusCls($oldStatus)) ?>"><?= $e($oldStatus !== '' ? $oldStatus : '—') ?></span>
                                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                                <span class="status <?= $e($statusCls($newStatus)) ?>"><?= $e($newStatus !== '' ? $newStatus : '—') ?></span>
                                            </span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($performedBy !== ''): ?>
                                            <span class="cell-by">
                                                <span class="mini-avatar" aria-hidden="true"><?= $e($performerInitial) ?></span>
                                                <?= $e($performedBy) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="cell-when">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-actions">
                                        <!-- Row actions — Bootstrap dropdown -->
                                        <div class="dropdown">
                                            <button type="button" class="icon-btn row-actions-btn" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Row actions">
                                                <i class="bi bi-three-dots" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="/order-history/view?id=<?= $hid ?>">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>View details
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-row-action="copy" data-order-number="<?= $e($orderNum) ?>">
                                                        <i class="bi bi-clipboard" aria-hidden="true"></i>Copy order #
                                                    </button>
                                                </li>
                                                <?php if ($orderId > 0): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="/orders?id=<?= $orderId ?>">
                                                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Open order
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="/order-history?order_id=<?= $orderId ?>">
                                                            <i class="bi bi-list-ul" aria-hidden="true"></i>All history for order
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="no-results" id="noResults" role="status">No records match your search.</div>

                <div class="table-footer">
                    <span id="showingLabel">
                        Showing 1–<?= number_format(count($history)) ?> of <?= number_format(count($history)) ?>
                    </span>
                    <div class="pagination" id="pagination" aria-label="Pagination"></div>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>


<!-- ==========================================================
     COMPONENT: Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>


<script>
/* =========================================================
   TOAST
   ========================================================= */
(function () {
    var stack = document.getElementById('toastStack');
    window.showToast = function (message, icon) {
        if (!stack) return;
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
})();

/* =========================================================
   FLASH MESSAGES
   ========================================================= */
<?php if ($flashSuccess !== null && $flashSuccess !== ''): ?>
    document.addEventListener('DOMContentLoaded', function () {
        window.showToast(<?= json_encode($flashSuccess) ?>, 'bi-check2-circle');
    });
<?php endif; ?>
<?php if ($flashError !== null && $flashError !== ''): ?>
    document.addEventListener('DOMContentLoaded', function () {
        window.showToast(<?= json_encode($flashError) ?>, 'bi-exclamation-circle');
    });
<?php endif; ?>

/* =========================================================
   NOTIFICATIONS + USER MENU (Bootstrap dropdowns)
   ========================================================= */
(function () {
    document.querySelectorAll('[data-notif]').forEach(function (item) {
        item.addEventListener('click', function () {
            var t = item.querySelector('.notif-title');
            window.showToast(t ? t.textContent : 'Notification', 'bi-bell');
        });
    });
    document.querySelectorAll('.dropdown-menu [data-user-action]').forEach(function (item) {
        item.addEventListener('click', function () {
            var action = item.getAttribute('data-user-action');
            window.showToast(
                action === 'logout' ? 'Signed out (demo)' : 'Opening ' + item.textContent.trim().toLowerCase() + '…',
                action === 'logout' ? 'bi-box-arrow-right' : 'bi-arrow-right'
            );
        });
    });
    var markAll = document.getElementById('markAllRead');
    if (markAll) {
        markAll.addEventListener('click', function () {
            var dot = document.getElementById('notifDot');
            if (dot) dot.style.display = 'none';
            window.showToast('All notifications marked as read', 'bi-check2-all');
        });
    }
})();

/* =========================================================
   HISTORY TABLE — client-side search + pagination
   (Action filter is server-driven: clicking a dropdown item
    navigates to /order-history?action=X so the controller can
    re-query the dataset correctly.)
   ========================================================= */
(function () {
    var body = document.getElementById('historyBody');
    if (!body) return;

    var empty        = document.getElementById('noResults');
    var showing      = document.getElementById('showingLabel');
    var countEl      = document.getElementById('filterCount');
    var pagination   = document.getElementById('pagination');
    var filterSearch = document.getElementById('filterSearch');
    var topSearch    = document.getElementById('topSearch');

    var PAGE_SIZE = 12;
    var currentSearch = '';
    var currentPage   = 1;

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr'));

    function filteredRows() {
        return allRows.filter(function (row) {
            if (!currentSearch) return true;
            var haystack = [
                row.getAttribute('data-order-number') || '',
                row.getAttribute('data-order-id')     || '',
                row.getAttribute('data-performed-by') || '',
                row.getAttribute('data-details')      || '',
                row.getAttribute('data-action')       || '',
                row.getAttribute('data-history-id')   || ''
            ].join(' ').toLowerCase();
            return haystack.indexOf(currentSearch) !== -1;
        });
    }

    function syncInputs(value) {
        if (filterSearch && filterSearch.value !== value) filterSearch.value = value;
        if (topSearch    && topSearch.value    !== value) topSearch.value    = value;
    }

    function setSearch(value) {
        currentSearch = String(value || '').trim().toLowerCase();
        currentPage = 1;
        syncInputs(value);
        render();
    }

    function render() {
        var matched = filteredRows();
        var total = matched.length;
        var pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > pages) currentPage = pages;
        var start = (currentPage - 1) * PAGE_SIZE;
        var end   = start + PAGE_SIZE;

        allRows.forEach(function (row) { row.style.display = 'none'; });
        matched.slice(start, end).forEach(function (row) { row.style.display = ''; });

        if (empty) empty.style.display = total === 0 ? 'block' : 'none';
        if (countEl) countEl.textContent = total + (total === 1 ? ' record' : ' records');

        if (showing) {
            showing.textContent = total === 0
                ? 'No records'
                : 'Showing ' + (start + 1) + '–' + Math.min(end, total) + ' of ' + total;
        }

        renderPagination(pages);
    }

    function renderPagination(pages) {
        if (!pagination) return;
        pagination.innerHTML = '';

        function btn(label, page, opts) {
            opts = opts || {};
            var b = document.createElement('button');
            b.type = 'button';
            b.innerHTML = label;
            if (opts.active) { b.classList.add('active'); b.setAttribute('aria-current', 'page'); }
            if (opts.disabled) b.disabled = true;
            if (opts.ariaLabel) b.setAttribute('aria-label', opts.ariaLabel);
            b.addEventListener('click', function () { currentPage = page; render(); });
            return b;
        }

        pagination.appendChild(btn('<i class="bi bi-chevron-left" aria-hidden="true"></i>', currentPage - 1, {
            disabled: currentPage === 1, ariaLabel: 'Previous page'
        }));

        var range = [];
        if (pages <= 7) {
            for (var i = 1; i <= pages; i++) range.push(i);
        } else {
            range.push(1);
            if (currentPage > 3) range.push('…');
            for (var j = Math.max(2, currentPage - 1); j <= Math.min(pages - 1, currentPage + 1); j++) range.push(j);
            if (currentPage < pages - 2) range.push('…');
            range.push(pages);
        }

        range.forEach(function (p) {
            if (p === '…') {
                var el = document.createElement('span');
                el.textContent = '…';
                el.style.cssText = 'display:inline-flex;width:28px;height:28px;align-items:center;justify-content:center;color:var(--text-tertiary);font-size:12px;';
                pagination.appendChild(el);
            } else {
                pagination.appendChild(btn(String(p), p, { active: p === currentPage }));
            }
        });

        pagination.appendChild(btn('<i class="bi bi-chevron-right" aria-hidden="true"></i>', currentPage + 1, {
            disabled: currentPage === pages, ariaLabel: 'Next page'
        }));
    }

    if (filterSearch) filterSearch.addEventListener('input', function () { setSearch(filterSearch.value); });
    if (topSearch)    topSearch.addEventListener('input',    function () { setSearch(topSearch.value); });

    /* Row actions (Bootstrap handles menu open/close) */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-row-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-row-action');

        if (action === 'copy') {
            var num = btn.getAttribute('data-order-number') || '';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(num).then(function () {
                    window.showToast('Order number copied', 'bi-clipboard-check');
                }).catch(function () {
                    window.showToast('Order: ' + num, 'bi-clipboard');
                });
            } else {
                window.showToast('Order: ' + num, 'bi-clipboard');
            }
        }
    });

    /* ⌘K / Ctrl+K focuses the in-page filter */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (filterSearch) filterSearch.focus();
        }
    });

    render();
})();
</script>
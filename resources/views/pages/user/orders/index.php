<?php
/**
 * RESTOCK ORDERS PAGE
 * Rendered inside app.php. Sidebar and shell come from the layout.
 *
 * Expected variables (all optional — falls back to empty defaults):
 *   @var array      $orders
 *   @var array      $products
 *   @var int        $totalOrders, $pendingOrders, $orderedOrders,
 *                   $receivedOrders, $cancelledOrders
 *   @var float      $totalRestockCost
 *   @var array|null $selectedOrder
 *   @var array      $orderItems
 *   @var string     $csrfToken
 */

$orders           = $orders           ?? [];
$products         = $products         ?? [];
$totalOrders      = $totalOrders      ?? count($orders);
$pendingOrders    = $pendingOrders    ?? 0;
$orderedOrders    = $orderedOrders    ?? 0;
$receivedOrders   = $receivedOrders   ?? 0;
$cancelledOrders  = $cancelledOrders  ?? 0;
$totalRestockCost = $totalRestockCost ?? 0.0;
$selectedOrder    = $selectedOrder    ?? null;
$orderItems       = $orderItems       ?? [];
$csrfToken        = $csrfToken        ?? '';

/* PHP 5.4+ safe — no arrow functions, no return-type-on-closure tricks. */
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$pick = static function (array $rec, array $keys, string $default = '') {
    foreach ($keys as $k) {
        if (isset($rec[$k]) && $rec[$k] !== '' && $rec[$k] !== null) return (string) $rec[$k];
    }
    return $default;
};

$statusMeta = static function (string $s) {
    switch (strtoupper($s)) {
        case 'PENDING':   return ['Pending',   'status-warning'];
        case 'ORDERED':   return ['Ordered',   'status-info'];
        case 'RECEIVED':  return ['Received',  'status-success'];
        case 'CANCELLED': return ['Cancelled', 'status-danger'];
        default:          return [$s ?: 'Unknown', 'status-info'];
    }
};

$fmtWhen = static function (string $ts) {
    if ($ts === '') return '—';
    $t = strtotime($ts);
    return $t === false ? $ts : date('M j, Y · g:i A', $t);
};

/* Known suppliers derived from existing orders */
$knownSuppliers = [];
foreach ($orders as $o) {
    $sid = (int) ($o['supplier_id'] ?? 0);
    if ($sid > 0 && !isset($knownSuppliers[$sid])) {
        $knownSuppliers[$sid] = $o['supplier_name'] ?? null;
    }
}

/* Flash messages from session */
$flashSuccess = $_SESSION['success'] ?? null;
$flashError   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

/* Product lookup for add-item form */
$productOptions = [];
foreach ($products as $p) {
    $pid = (int) ($p['id'] ?? 0);
    if (!$pid) continue;
    $productOptions[$pid] = [
        'name' => (string) ($p['name'] ?? ''),
        'sku'  => (string) ($p['sku']  ?? ''),
    ];
}
?>


<style>
    /* ==========================================================
       ORDERS PAGE — page-specific styles
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

    .cost-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--success-bg);
        color: var(--success);
        font-size: 12px;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .cost-badge i { font-size: 12px; }

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
    .stat-dot.is-pending   { background: var(--warning); }
    .stat-dot.is-ordered   { background: var(--info); }
    .stat-dot.is-received  { background: var(--success); }
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
    .stat-value.is-pending   { color: var(--warning); }
    .stat-value.is-ordered   { color: var(--info); }
    .stat-value.is-received  { color: var(--success); }
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
    .filter-search { width: 260px; }
    .filter-count {
        font-size: 12px;
        color: var(--text-tertiary);
        font-variant-numeric: tabular-nums;
    }
    @media (max-width: 767.98px) { .filter-search { width: 100%; } }

    /* ---------- Order ref badge ---------- */
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

    /* ---------- Order rows ---------- */
    .order-row { cursor: pointer; transition: background-color 120ms ease; }
    .order-row > td { transition: box-shadow 120ms ease; }
    .order-row:hover { background: var(--surface-muted); }
    .order-row:hover > td:first-child { box-shadow: inset 3px 0 0 var(--text); }

    .cell-items {
        font-size: 13px;
        color: var(--text-secondary);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .cell-total {
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .cell-when {
        font-size: 12px;
        color: var(--text-secondary);
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .cell-actions { width: 44px; text-align: right; }
    .row-actions-btn { width: 28px; height: 28px; font-size: 14px; }

    /* ---------- Order modal meta ---------- */
    .meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px 24px;
    }
    .meta-item { min-width: 0; }
    .meta-label {
        font-size: 11px;
        font-weight: 500;
        color: var(--text-tertiary);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin: 0 0 4px;
    }
    .meta-value {
        font-size: 13px;
        color: var(--text);
        font-weight: 500;
        margin: 0;
        word-break: break-word;
    }
    .meta-value.is-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .meta-value.is-muted { color: var(--text-secondary); font-weight: 400; }

    /* ---------- Items table ---------- */
    .items-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
        font-size: 13px;
    }
    .items-table thead th {
        text-align: left;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--text-tertiary);
        padding: 10px 12px;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
        background: var(--surface-muted);
    }
    .items-table th.num { text-align: right; }
    .items-table tbody td {
        padding: 12px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
    }
    .items-table tbody tr { transition: background-color 100ms ease; }
    .items-table tbody tr:hover { background: var(--surface-muted); }
    .items-table tbody tr:last-child td { border-bottom: 0; }
    .items-table td.num { text-align: right; font-variant-numeric: tabular-nums; }
    .items-table .item-name { font-weight: 500; color: var(--text); }
    .items-table .item-sku { font-size: 11px; color: var(--text-tertiary); margin-top: 2px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    .items-table .item-subtotal { font-weight: 600; color: var(--text); }

    /* ---------- Inline inputs ---------- */
    .inline-input {
        width: 76px;
        height: 30px;
        padding: 0 8px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: var(--surface);
        font-family: inherit;
        font-size: 12px;
        color: var(--text);
        font-variant-numeric: tabular-nums;
        text-align: right;
        transition: border-color 120ms ease, box-shadow 120ms ease;
    }
    .inline-input:focus {
        outline: none;
        border-color: var(--border-strong);
        box-shadow: 0 0 0 3px rgba(0,0,0,0.06);
    }
    .inline-input.is-wide { width: 96px; }

    .btn-icon-sm {
        width: 26px;
        height: 26px;
        padding: 0;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: var(--text-secondary);
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 120ms ease, color 120ms ease;
    }
    .btn-icon-sm:hover { background: var(--surface-hover); color: var(--text); }
    .btn-icon-sm.is-danger { color: var(--danger); }
    .btn-icon-sm.is-danger:hover { background: var(--danger-bg); color: var(--danger); }

    /* ---------- Add item form ---------- */
    .items-table tr.add-row td {
        background: var(--surface-muted);
        border-bottom: 0;
        border-top: 1px solid var(--border);
        padding: 14px 12px;
    }
    .add-item-form {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 8px;
        align-items: center;
    }
    .add-item-form select,
    .add-item-form input {
        height: 34px;
        padding: 0 10px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: var(--surface);
        font-family: inherit;
        font-size: 12px;
        color: var(--text);
        transition: border-color 120ms ease, box-shadow 120ms ease;
    }
    .add-item-form select:focus,
    .add-item-form input:focus {
        outline: none;
        border-color: var(--border-strong);
        box-shadow: 0 0 0 3px rgba(0,0,0,0.06);
    }

    .items-empty {
        padding: 28px 12px;
        text-align: center;
        color: var(--text-tertiary);
        font-size: 12px;
    }

    /* ---------- Modal icon ---------- */
    .order-modal-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .order-modal-icon.status-warning { background: var(--warning-bg); color: var(--warning); }
    .order-modal-icon.status-info    { background: var(--info-bg);    color: var(--info); }
    .order-modal-icon.status-success { background: var(--success-bg); color: var(--success); }
    .order-modal-icon.status-danger  { background: var(--danger-bg);  color: var(--danger); }

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

    /* ---------- Dropdown enhancement (page-level) ---------- */
    /* Make sure menus paint above the topbar / sticky elements */
    .dropdown-menu-app.is-open {
        z-index: 1060;
        animation: ddIn 140ms cubic-bezier(0.2, 0.8, 0.2, 1);
        box-shadow: 0 12px 32px -8px rgba(0, 0, 0, 0.18), 0 4px 12px -4px rgba(0, 0, 0, 0.10);
    }
    @keyframes ddIn {
        from { opacity: 0; transform: translateY(-4px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Toast */
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
</style>


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


<main class="dashboard-content">

    <!-- ==========================================================
         COMPONENT: Page header
         ========================================================== -->
    <section class="page-header" aria-labelledby="pageTitle">
        <div class="min-w-0">
            <h1 class="page-title" id="pageTitle">Restock orders</h1>
            <p class="page-subtitle">
                Track restocking orders across suppliers
                <span class="cost-badge">
                    <i class="bi bi-cash-stack" aria-hidden="true"></i>
                    ₱<?= number_format((float) $totalRestockCost, 2) ?> received
                </span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn-primary-app" id="addOrderBtn">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>New order
            </button>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Stats strip
         ========================================================== -->
    <section class="stats-strip" aria-label="Order statistics">
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot"></span>
                <p class="stat-label">Total orders</p>
            </div>
            <p class="stat-value"><?= number_format($totalOrders) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-pending"></span>
                <p class="stat-label">Pending</p>
            </div>
            <p class="stat-value is-pending"><?= number_format($pendingOrders) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-ordered"></span>
                <p class="stat-label">Ordered</p>
            </div>
            <p class="stat-value is-ordered"><?= number_format($orderedOrders) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-received"></span>
                <p class="stat-label">Received</p>
            </div>
            <p class="stat-value is-received"><?= number_format($receivedOrders) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-cancelled"></span>
                <p class="stat-label">Cancelled</p>
            </div>
            <p class="stat-value is-cancelled"><?= number_format($cancelledOrders) ?></p>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Filter bar
         ========================================================== -->
    <section class="filter-bar" aria-label="Filter orders">
        <div class="filter-bar__group">
            <div class="search-wrap filter-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="filterSearch" class="search-input" placeholder="Filter by order # or supplier..." aria-label="Filter orders" autocomplete="off">
            </div>

            <div class="dropdown-app">
                <button type="button" class="btn-secondary" id="statusFilterBtn" data-dropdown-toggle="statusFilterMenu" aria-haspopup="menu" aria-expanded="false">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    <span id="statusFilterLabel">All statuses</span>
                    <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
                </button>
                <div class="dropdown-menu-app" id="statusFilterMenu" role="menu">
                    <div class="dropdown-label-app">Order status</div>
                    <button type="button" class="dropdown-item-app" data-status="">All statuses</button>
                    <button type="button" class="dropdown-item-app" data-status="PENDING">Pending</button>
                    <button type="button" class="dropdown-item-app" data-status="ORDERED">Ordered</button>
                    <button type="button" class="dropdown-item-app" data-status="RECEIVED">Received</button>
                    <button type="button" class="dropdown-item-app" data-status="CANCELLED">Cancelled</button>
                </div>
            </div>
        </div>

        <div class="filter-bar__group">
            <span class="filter-count" id="filterCount"><?= number_format($totalOrders) ?> order<?= $totalOrders === 1 ? '' : 's' ?></span>
            <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
                <i class="bi bi-x" aria-hidden="true"></i>Clear
            </button>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Orders table
         ========================================================== -->
    <section class="section-gap">
        <div class="panel">

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-truck" aria-hidden="true"></i></div>
                    <h3 class="empty-title">No restock orders yet</h3>
                    <p class="empty-text">Create your first restock order to request products from a supplier.</p>
                    <button type="button" class="btn-primary-app" onclick="var b = document.getElementById('addOrderBtn'); if (b) b.click();">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>New order
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table activity-table align-middle">
                        <caption>Restock orders: order number, supplier, status, items, total and creation date.</caption>
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Supplier</th>
                                <th scope="col">Status</th>
                                <th scope="col">Items</th>
                                <th scope="col">Total</th>
                                <th scope="col">Created</th>
                                <th scope="col" style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody id="ordersBody">
                            <?php foreach ($orders as $o):
                                $oid    = (int) ($o['id'] ?? 0);
                                $num    = (string) ($o['order_number'] ?? $o['number'] ?? ('#' . $oid));
                                $sid    = (int) ($o['supplier_id'] ?? 0);
                                $sname  = (string) ($o['supplier_name'] ?? '');
                                $status = strtoupper((string) ($o['status'] ?? 'PENDING'));
                                $total  = (float) ($o['total_amount'] ?? 0);
                                $items  = (int) ($o['items_count'] ?? $o['item_count'] ?? 0);
                                $when   = $pick($o, ['created_at', 'created_on', 'date', 'created'], '');

                                $meta = $statusMeta($status);
                                $statusLabel = $meta[0];
                                $statusCls   = $meta[1];
                            ?>
                                <tr class="order-row"
                                    data-order-id="<?= $oid ?>"
                                    data-order-number="<?= $e($num) ?>"
                                    data-status="<?= $e($status) ?>"
                                    data-supplier-name="<?= $e($sname) ?>"
                                    onclick="if (!event.target.closest('a,button,.dropdown-menu-app')) { window.location.href = '/orders?id=<?= $oid ?>'; }"
                                >
                                    <td>
                                        <a class="order-ref" href="/orders?id=<?= $oid ?>">
                                            <i class="bi bi-receipt" aria-hidden="true"></i>
                                            <?= $e($num) ?>
                                        </a>
                                    </td>
                                    <td class="cell-muted">
                                        <?= $sname !== '' ? $e($sname) : 'Supplier #' . $sid ?>
                                    </td>
                                    <td><span class="status <?= $statusCls ?>"><?= $e($statusLabel) ?></span></td>
                                    <td class="cell-items"><?= $items > 0 ? number_format($items) : '—' ?></td>
                                    <td class="cell-total">₱<?= number_format($total, 2) ?></td>
                                    <td class="cell-when"><?= $e($fmtWhen($when)) ?></td>
                                    <td class="cell-actions">
                                        <div class="dropdown-app">
                                            <button type="button" class="icon-btn row-actions-btn" data-dropdown-toggle="orderMenu-<?= $oid ?>" aria-label="Row actions" aria-haspopup="menu" aria-expanded="false">
                                                <i class="bi bi-three-dots" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu-app" id="orderMenu-<?= $oid ?>" role="menu">
                                                <button type="button" class="dropdown-item-app" data-row-action="view"><i class="bi bi-eye" aria-hidden="true"></i>View / manage</button>
                                                <button type="button" class="dropdown-item-app" data-row-action="copy"><i class="bi bi-clipboard" aria-hidden="true"></i>Copy order #</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="no-results" id="noResults" role="status">No orders match your filters.</div>

                <div class="table-footer">
                    <span id="showingLabel">
                        Showing 1–<?= number_format($totalOrders) ?> of <?= number_format($totalOrders) ?>
                    </span>
                    <div class="pagination" id="pagination" aria-label="Pagination"></div>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>


<!-- ==========================================================
     COMPONENT: New order modal
     ========================================================== -->
<div class="modal fade" id="newOrderModal" tabindex="-1" aria-labelledby="newOrderTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <form method="post" action="/orders/create">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="newOrderTitle">New restock order</h2>
                        <p class="modal-subtitle-app">Create a pending order and pick items next.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <?php if (!empty($knownSuppliers)): ?>
                        <div class="form-row">
                            <div>
                                <label class="form-label-app" for="fieldSupplier">Supplier</label>
                                <select class="form-select-app" id="fieldSupplier" name="supplier_id" required>
                                    <option value="">Select a supplier…</option>
                                    <?php foreach ($knownSuppliers as $sid => $sname):
                                        $label = $sname !== null && $sname !== '' ? $sname : ('Supplier #' . $sid);
                                    ?>
                                        <option value="<?= (int) $sid ?>"><?= $e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p style="font-size:11px;color:var(--text-tertiary);margin:6px 0 0">
                                    Suppliers are pulled from your existing orders.
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-row">
                            <div>
                                <label class="form-label-app" for="fieldSupplier">Supplier ID</label>
                                <input class="form-control-app" type="number" id="fieldSupplier" name="supplier_id" min="1" required placeholder="e.g. 1">
                                <p style="font-size:11px;color:var(--text-tertiary);margin:6px 0 0">
                                    Enter the supplier's ID. Wire this up to a supplier list when ready.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>Create order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php if ($selectedOrder !== null):
    $soid     = (int) ($selectedOrder['id'] ?? 0);
    $sonum    = (string) ($selectedOrder['order_number'] ?? $selectedOrder['number'] ?? ('#' . $soid));
    $sosid    = (int) ($selectedOrder['supplier_id'] ?? 0);
    $sosname  = (string) ($selectedOrder['supplier_name'] ?? '');
    $sostatus = strtoupper((string) ($selectedOrder['status'] ?? 'PENDING'));
    $sototal  = (float) ($selectedOrder['total_amount'] ?? 0);
    $sowhen   = $pick($selectedOrder, ['created_at', 'created_on', 'date', 'created'], '');

    $soMeta       = $statusMeta($sostatus);
    $soStatusLabel = $soMeta[0];
    $soStatusCls   = $soMeta[1];

    $isPending    = $sostatus === 'PENDING';
    $isOrdered    = $sostatus === 'ORDERED';
    $canCancel    = $isPending || $isOrdered;
    $canSubmit    = $isPending && !empty($orderItems);
    $canEditItems = $isPending;

    $statusIconMap = [
        'PENDING'   => 'bi-hourglass-split',
        'ORDERED'   => 'bi-send',
        'RECEIVED'  => 'bi-check2-circle',
        'CANCELLED' => 'bi-x-circle',
    ];
    $statusIcon = $statusIconMap[$sostatus] ?? 'bi-receipt';
?>
<!-- ==========================================================
     COMPONENT: Order detail modal (auto-opens on ?id=X)
     ========================================================== -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-app">

            <div class="modal-header-app">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <span class="order-modal-icon <?= $soStatusCls ?>">
                        <i class="bi <?= $statusIcon ?>" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="modal-title-app" id="orderModalTitle"><?= $e($sonum) ?></h2>
                        <p class="modal-subtitle-app"><?= $e($soStatusLabel) ?> order</p>
                    </div>
                </div>
                <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-body-app">
                <div class="meta-grid">
                    <div class="meta-item">
                        <p class="meta-label">Supplier</p>
                        <p class="meta-value is-muted">
                            <?= $sosname !== '' ? $e($sosname) : 'Supplier #' . $sosid ?>
                        </p>
                    </div>
                    <div class="meta-item">
                        <p class="meta-label">Created</p>
                        <p class="meta-value is-muted"><?= $e($fmtWhen($sowhen)) ?></p>
                    </div>
                    <div class="meta-item">
                        <p class="meta-label">Status</p>
                        <p class="meta-value"><span class="status <?= $soStatusCls ?>"><?= $e($soStatusLabel) ?></span></p>
                    </div>
                    <div class="meta-item">
                        <p class="meta-label">Total</p>
                        <p class="meta-value is-mono">₱<?= number_format($sototal, 2) ?></p>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between" style="margin-top:26px;margin-bottom:8px">
                    <p class="meta-label" style="margin:0">Items</p>
                    <span class="filter-count"><?= count($orderItems) ?> item<?= count($orderItems) === 1 ? '' : 's' ?></span>
                </div>

                <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col" class="num" style="width:90px">Qty</th>
                                <th scope="col" class="num" style="width:110px">Unit price</th>
                                <th scope="col" class="num" style="width:110px">Subtotal</th>
                                <th scope="col" class="num" style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orderItems)): ?>
                                <tr><td colspan="5"><div class="items-empty">No items yet<?= $isPending ? ' — add one below.' : '.' ?></div></td></tr>
                            <?php else: ?>
                                <?php foreach ($orderItems as $it):
                                    $iid = (int) ($it['id'] ?? 0);
                                    $pid = (int) ($it['product_id'] ?? 0);
                                    $pname = (string) ($it['product_name'] ?? ($productOptions[$pid]['name'] ?? ('Product #' . $pid)));
                                    $psku  = (string) ($it['sku'] ?? ($productOptions[$pid]['sku'] ?? ''));
                                    $qty   = (int) ($it['quantity'] ?? 0);
                                    $price = (float) ($it['unit_price'] ?? 0);
                                    $sub   = $qty * $price;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="item-name"><?= $e($pname) ?></div>
                                            <?php if ($psku !== ''): ?>
                                                <div class="item-sku"><?= $e($psku) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="num">
                                            <?php if ($canEditItems): ?>
                                                <form id="updateForm-<?= $iid ?>" method="post" action="/orders/items/update" style="display:none">
                                                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                                    <input type="hidden" name="item_id" value="<?= $iid ?>">
                                                </form>
                                                <input class="inline-input" type="number" name="quantity" min="1" value="<?= $qty ?>" form="updateForm-<?= $iid ?>">
                                            <?php else: ?>
                                                <?= number_format($qty) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="num">
                                            <?php if ($canEditItems): ?>
                                                <input class="inline-input is-wide" type="number" name="unit_price" min="0" step="0.01" value="<?= number_format($price, 2, '.', '') ?>" form="updateForm-<?= $iid ?>">
                                            <?php else: ?>
                                                ₱<?= number_format($price, 2) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="num item-subtotal">₱<?= number_format($sub, 2) ?></td>
                                        <td class="num">
                                            <?php if ($canEditItems): ?>
                                                <button type="submit" form="updateForm-<?= $iid ?>" class="btn-icon-sm" title="Update item">
                                                    <i class="bi bi-check2" aria-hidden="true"></i>
                                                </button>
                                                <form id="deleteForm-<?= $iid ?>" method="post" action="/orders/items/delete" style="display:inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                                    <input type="hidden" name="item_id" value="<?= $iid ?>">
                                                    <button type="submit" class="btn-icon-sm is-danger" title="Remove item" onclick="return confirm('Remove this item from the order?')">
                                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ($canEditItems): ?>
                                <tr class="add-row">
                                    <td colspan="5">
                                        <form class="add-item-form" method="post" action="/orders/items/add">
                                            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                            <input type="hidden" name="order_id" value="<?= $soid ?>">

                                            <select name="product_id" required>
                                                <option value="">Add a product…</option>
                                                <?php foreach ($productOptions as $pid => $pinfo): ?>
                                                    <option value="<?= (int) $pid ?>"><?= $e($pinfo['name']) ?><?= $pinfo['sku'] !== '' ? ' · ' . $e($pinfo['sku']) : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>

                                            <input type="number" name="quantity" min="1" required placeholder="Qty" aria-label="Quantity">
                                            <input type="number" name="unit_price" min="0" step="0.01" required placeholder="Unit price" aria-label="Unit price">

                                            <button type="submit" class="btn-secondary" style="height:34px">
                                                <i class="bi bi-plus-lg" aria-hidden="true"></i>Add
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer-app">
                <?php if ($canCancel): ?>
                    <form method="post" action="/orders/cancel" style="margin-right:auto">
                        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                        <input type="hidden" name="order_id" value="<?= $soid ?>">
                        <button type="submit" class="btn-secondary" style="color:var(--danger);border-color:var(--border)" onclick="return confirm('Cancel this order?')">
                            <i class="bi bi-x-circle" aria-hidden="true"></i>Cancel order
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($isPending): ?>
                    <form method="post" action="/orders/submit" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                        <input type="hidden" name="order_id" value="<?= $soid ?>">
                        <button type="submit" class="btn-primary-app" <?= !$canSubmit ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>>
                            <i class="bi bi-send" aria-hidden="true"></i>Place order
                        </button>
                    </form>
                <?php endif; ?>

                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
<?php endif; ?>


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
   DROPDOWNS — robust, delegated, viewport-aware.
   ========================================================= */
(function () {
    'use strict';

    var openMenuEl = null;

    function resetMenu(menu) {
        if (!menu) return;
        menu.classList.remove('is-open');
        menu.style.position = '';
        menu.style.top = '';
        menu.style.left = '';
        menu.style.right = '';
        menu.style.bottom = '';
        menu.style.visibility = '';
        menu.style.display = '';
        menu.style.zIndex = '';
    }

    function closeAll() {
        if (openMenuEl) { resetMenu(openMenuEl); openMenuEl = null; }
        document.querySelectorAll('.dropdown-menu-app.is-open').forEach(resetMenu);
        document.querySelectorAll('[data-dropdown-toggle][aria-expanded="true"]').forEach(function (b) {
            b.setAttribute('aria-expanded', 'false');
        });
    }

    function openDropdown(btn, menu) {
        closeAll();

        // 1) Show menu so it has layout; hide visually while we measure.
        menu.classList.add('is-open');
        menu.style.visibility = 'hidden';

        // 2) Fallback: if the layout didn't define display for `.is-open`,
        //    force it so the menu can be measured and shown.
        if (getComputedStyle(menu).display === 'none') {
            menu.style.display = 'block';
        }

        var mw = menu.offsetWidth  || 200;
        var mh = menu.offsetHeight || 120;

        var pad = 8, gap = 6;
        var vw  = window.innerWidth;
        var vh  = window.innerHeight;
        var r   = btn.getBoundingClientRect();

        var top  = r.bottom + gap;
        var left = r.right - mw;

        // Flip up if it would overflow the viewport bottom.
        if (top + mh > vh - pad) {
            var above = r.top - mh - gap;
            top = above >= pad ? above : Math.max(pad, vh - mh - pad);
        }
        if (top < pad) top = pad;

        // Clamp horizontally.
        if (left < pad) left = pad;
        if (left + mw > vw - pad) left = vw - mw - pad;
        if (left < pad) left = pad;

        menu.style.position = 'fixed';
        menu.style.top      = top + 'px';
        menu.style.left     = left + 'px';
        menu.style.right    = 'auto';
        menu.style.bottom   = 'auto';
        menu.style.visibility = '';

        btn.setAttribute('aria-expanded', 'true');
        openMenuEl = menu;
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-dropdown-toggle]');
        if (btn) {
            e.preventDefault();
            var menu = document.getElementById(btn.getAttribute('data-dropdown-toggle'));
            if (!menu) return;
            var wasOpen = menu.classList.contains('is-open');
            closeAll();
            if (!wasOpen) openDropdown(btn, menu);
            return;
        }

        // Any click inside an open menu: let the item's own handler fire,
        // then close (keeps menus from getting "stuck").
        if (e.target.closest('.dropdown-menu-app')) {
            closeAll();
            return;
        }

        closeAll();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAll();
    });

    // Close on scroll (capture so we catch any scrollable ancestor) and resize,
    // since a fixed-positioned menu would otherwise detach from its trigger.
    window.addEventListener('scroll', closeAll, { passive: true, capture: true });
    window.addEventListener('resize', closeAll, { passive: true });
})();

/* =========================================================
   NOTIFICATIONS + USER MENU
   ========================================================= */
(function () {
    document.querySelectorAll('[data-notif]').forEach(function (item) {
        item.addEventListener('click', function () {
            var t = item.querySelector('.notif-title');
            window.showToast(t ? t.textContent : 'Notification', 'bi-bell');
        });
    });
    document.querySelectorAll('#userMenu [data-user-action]').forEach(function (item) {
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
   NEW ORDER MODAL
   ========================================================= */
(function () {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
    var modalEl = document.getElementById('newOrderModal');
    var addBtn  = document.getElementById('addOrderBtn');
    if (!modalEl || !addBtn) return;
    var modal = new bootstrap.Modal(modalEl);
    addBtn.addEventListener('click', function () { modal.show(); });
})();

/* =========================================================
   ORDER DETAIL MODAL — auto-opens on ?id=X
   ========================================================= */
(function () {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
    var modalEl = document.getElementById('orderModal');
    if (!modalEl) return;
    var modal = new bootstrap.Modal(modalEl);
    modal.show();

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (new URLSearchParams(window.location.search).has('id')) {
            window.location.href = '/orders';
        }
    });
})();

/* =========================================================
   ORDERS TABLE — search, filter, pagination
   ========================================================= */
(function () {
    var body = document.getElementById('ordersBody');
    if (!body) return;

    var empty        = document.getElementById('noResults');
    var showing      = document.getElementById('showingLabel');
    var countEl      = document.getElementById('filterCount');
    var pagination   = document.getElementById('pagination');
    var filterSearch = document.getElementById('filterSearch');
    var topSearch    = document.getElementById('ordersSearch');
    var statusLabel  = document.getElementById('statusFilterLabel');
    var clearBtn     = document.getElementById('clearFiltersBtn');

    var PAGE_SIZE = 12;
    var currentSearch = '';
    var currentStatus = '';
    var currentPage   = 1;

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr'));

    function filteredRows() {
        return allRows.filter(function (row) {
            var statusOk = !currentStatus || row.getAttribute('data-status') === currentStatus;
            var haystack = (row.getAttribute('data-order-number') || '') + ' ' + (row.getAttribute('data-supplier-name') || '');
            var searchOk = !currentSearch || haystack.toLowerCase().indexOf(currentSearch) !== -1;
            return statusOk && searchOk;
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
        if (countEl) countEl.textContent = total + (total === 1 ? ' order' : ' orders');
        if (clearBtn) clearBtn.hidden = !(currentSearch || currentStatus);

        if (showing) {
            showing.textContent = total === 0
                ? 'No orders'
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

    if (filterSearch) {
        filterSearch.addEventListener('input', function () { setSearch(filterSearch.value); });
    }
    if (topSearch) {
        topSearch.addEventListener('input', function () { setSearch(topSearch.value); });
    }

    function markFilterSelection(item) {
        var menu = item.closest('.dropdown-menu-app');
        if (!menu) return;
        menu.querySelectorAll('.dropdown-item-app.is-selected').forEach(function (el) {
            el.classList.remove('is-selected');
        });
        item.classList.add('is-selected');
    }

    document.querySelectorAll('#statusFilterMenu [data-status]').forEach(function (item, index) {
        if (index === 0) item.classList.add('is-selected');
        item.addEventListener('click', function () {
            currentStatus = item.getAttribute('data-status') || '';
            if (statusLabel) statusLabel.textContent = item.textContent.trim();
            markFilterSelection(item);
            currentPage = 1;
            render();
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            currentStatus = '';
            if (statusLabel) statusLabel.textContent = 'All statuses';
            document.querySelectorAll('#statusFilterMenu [data-status]').forEach(function (el, i) {
                el.classList.toggle('is-selected', i === 0);
            });
            setSearch('');
        });
    }

    /* Row actions */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-row-action]');
        if (!btn) return;
        var row = btn.closest('tr.order-row');
        if (!row) return;
        var action = btn.getAttribute('data-row-action');

        if (action === 'view') {
            window.location.href = '/orders?id=' + row.getAttribute('data-order-id');
        } else if (action === 'copy') {
            var num = row.getAttribute('data-order-number');
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
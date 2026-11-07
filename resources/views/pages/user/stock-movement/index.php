<?php
/**
 * STOCK MOVEMENTS PAGE
 * Rendered inside app.php. Sidebar and shell come from the layout.
 *
 * Expected variables (all optional — falls back to empty defaults):
 *   @var array      $movements
 *   @var array      $products
 *   @var int        $totalMovements, $stockInCount, $stockOutCount,
 *                   $adjustmentCount, $todayCount
 *   @var string     $csrfToken
 */

$movements       = $movements       ?? [];
$products        = $products        ?? [];
$totalMovements  = $totalMovements  ?? count($movements);
$stockInCount    = $stockInCount    ?? 0;
$stockOutCount   = $stockOutCount   ?? 0;
$adjustmentCount = $adjustmentCount ?? 0;
$todayCount      = $todayCount      ?? 0;
$csrfToken       = $csrfToken       ?? '';

/* Derive stats from $movements if not supplied */
if (empty($stockInCount) && empty($stockOutCount) && empty($adjustmentCount) && !empty($movements)) {
    $today = date('Y-m-d');
    foreach ($movements as $m) {
        $t = strtoupper((string) ($m['movement_type'] ?? ''));
        if ($t === 'IN') $stockInCount++;
        elseif ($t === 'OUT') $stockOutCount++;
        elseif ($t === 'ADJUSTMENT') $adjustmentCount++;
        $when = (string) ($m['created_at'] ?? $m['created_on'] ?? '');
        if ($when !== '' && strpos($when, $today) === 0) $todayCount++;
    }
}

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

$movementTypes = [
    'IN'         => ['label' => 'Stock In',    'cls' => 'is-in',         'icon' => 'bi-box-arrow-in-down',    'sign' => '+'],
    'OUT'        => ['label' => 'Stock Out',   'cls' => 'is-out',        'icon' => 'bi-box-arrow-up',         'sign' => '−'],
    'ADJUSTMENT' => ['label' => 'Adjustment',  'cls' => 'is-adjustment', 'icon' => 'bi-sliders',              'sign' => '±'],
    'RETURN'     => ['label' => 'Return',      'cls' => 'is-return',     'icon' => 'bi-arrow-return-left',    'sign' => '+'],
    'DAMAGE'     => ['label' => 'Damage',      'cls' => 'is-damage',     'icon' => 'bi-exclamation-triangle', 'sign' => '−'],
];

$typeMeta = static function (string $t) use ($movementTypes) {
    $key = strtoupper($t);
    return $movementTypes[$key] ?? ['label' => $t ?: 'Unknown', 'cls' => 'is-return', 'icon' => 'bi-arrow-left-right', 'sign' => ''];
};

$fmtWhen = static function (string $ts) {
    if ($ts === '') return '—';
    $t = strtotime($ts);
    return $t === false ? $ts : date('M j, Y · g:i A', $t);
};

/* Product lookup for the new-movement form */
$productOptions = [];
foreach ($products as $p) {
    $pid = (int) ($p['id'] ?? 0);
    if (!$pid) continue;
    $productOptions[$pid] = [
        'name' => (string) ($p['name'] ?? ''),
        'sku'  => (string) ($p['sku']  ?? ''),
    ];
}

/* Flash */
$flashSuccess = $_SESSION['success'] ?? null;
$flashError   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>


<style>
    /* ==========================================================
       STOCK MOVEMENTS — page-specific styles
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

    .today-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--info-bg);
        color: var(--info);
        font-size: 12px;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .today-badge i { font-size: 12px; }

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
    .stat-dot.is-in         { background: var(--success); }
    .stat-dot.is-out        { background: var(--danger); }
    .stat-dot.is-adjustment { background: var(--warning); }
    .stat-dot.is-today      { background: var(--info); }

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
    .stat-value.is-in         { color: var(--success); }
    .stat-value.is-out        { color: var(--danger); }
    .stat-value.is-adjustment { color: var(--warning); }
    .stat-value.is-today      { color: var(--info); }

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

    /* ---------- Filter bar ----------
       z-index fix: filter bar (2) sits above the table section (1),
       so the dropdown menu is never painted behind / under the table. */
    .filter-bar {
        position: relative;
        z-index: 2;
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
    .filter-bar .dropdown { position: relative; }
    .filter-search { width: 280px; }
    .filter-count {
        font-size: 12px;
        color: var(--text-tertiary);
        font-variant-numeric: tabular-nums;
    }
    @media (max-width: 767.98px) { .filter-search { width: 100%; } }

    /* Table section stays below the filter bar */
    .section-gap {
        position: relative;
        z-index: 1;
    }

    /* ---------- Bootstrap dropdown (overrides) ---------- */
    .dropdown-menu {
        --bs-dropdown-bg: var(--surface);
        --bs-dropdown-border-color: var(--border);
        --bs-dropdown-link-color: var(--text);
        --bs-dropdown-link-hover-bg: var(--surface-hover);
        --bs-dropdown-link-hover-color: var(--text);
        --bs-dropdown-link-active-bg: var(--surface-muted);
        --bs-dropdown-link-active-color: var(--text);
        z-index: 1050;
        border-radius: 12px;
        box-shadow: 0 20px 48px -12px rgba(15,15,15,0.14), 0 4px 12px -4px rgba(15,15,15,0.06);
        padding: 7px;
        font-size: 13px;
        min-width: 224px;
        margin-top: 8px !important;
    }
    .dropdown-item {
        border-radius: 8px;
        height: 38px;
        padding: 0 10px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        font-weight: 500;
    }
    .dropdown-item.active,
    .dropdown-item:active {
        font-weight: 600;
    }
    .dropdown-item.active::after {
        content: "";
        width: 6px;
        height: 10px;
        margin-left: auto;
        margin-right: 3px;
        border: solid currentColor;
        border-width: 0 1.5px 1.5px 0;
        transform: translateY(-1px) rotate(45deg);
        flex: 0 0 auto;
    }
    .dropdown-item i { font-size: 14px; color: var(--text-tertiary); width: 16px; flex: 0 0 auto; }
    .dropdown-item:hover i,
    .dropdown-item:focus i,
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

    /* ---------- Movement type badge ---------- */
    .type-badge {
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
    .type-badge i { font-size: 12px; }
    .type-badge.is-in         { background: var(--success-bg); color: var(--success); }
    .type-badge.is-out        { background: var(--danger-bg);  color: var(--danger); }
    .type-badge.is-adjustment { background: var(--warning-bg); color: var(--warning); }
    .type-badge.is-return     { background: var(--info-bg);    color: var(--info); }
    .type-badge.is-damage     { background: var(--danger-bg);  color: var(--danger); }

    /* ---------- Table cells ---------- */
    .movement-row { transition: background-color 120ms ease; }
    .movement-row:hover { background: var(--surface-muted); }

    .cell-product .name { font-weight: 500; color: var(--text); }
    .cell-product .sku  {
        font-size: 11px;
        color: var(--text-tertiary);
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        margin-top: 2px;
    }

    .qty-delta {
        font-size: 13px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .qty-delta.is-pos  { color: var(--success); }
    .qty-delta.is-neg  { color: var(--danger); }
    .qty-delta.is-zero { color: var(--text-secondary); }

    .flow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-variant-numeric: tabular-nums;
        color: var(--text-secondary);
        white-space: nowrap;
    }
    .flow .from { color: var(--text-tertiary); }
    .flow .to   { color: var(--text); font-weight: 600; }
    .flow i     { font-size: 10px; color: var(--text-tertiary); }

    .cell-remarks {
        font-size: 12px;
        color: var(--text-secondary);
        max-width: 260px;
        overflow: hidden;
        text-overflow: ellipsis;
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
    #typeFilterBtn { min-width: 150px; justify-content: space-between; }
</style>


<!-- ==========================================================
     COMPONENT: Topbar
     ========================================================== -->
<header class="topbar d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2 min-w-0">
        <button type="button" class="icon-btn d-xl-none" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open navigation menu">
            <i class="bi bi-list" style="font-size:20px" aria-hidden="true"></i>
        </button>
        <div class="breadcrumb-lite">Inventory <span style="color:var(--text-tertiary)">/</span> <strong>Stock movements</strong></div>
    </div>
    <div class="d-flex align-items-center gap-2 gap-md-3">

        <div class="search-wrap">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="topSearch" class="search-input" placeholder="Search movements..." aria-label="Search movements" autocomplete="off">
            <kbd>⌘K</kbd>
        </div>

        <!-- Notifications — Bootstrap dropdown -->
        <div class="dropdown">
            <button type="button" class="icon-btn" id="notificationsBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notifications">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="notif-dot" id="notifDot" aria-hidden="true"></span>
            </button>
            <div class="dropdown-menu notif-menu" aria-labelledby="notificationsBtn" style="min-width:300px;padding:8px">
                <div class="dropdown-label-app">Notifications</div>
                <div class="notif-item" data-notif>
                    <span class="notif-icon is-warning"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                    <div class="notif-body">
                        <p class="notif-title"><?= number_format($stockOutCount) ?> stock-out movement<?= $stockOutCount === 1 ? '' : 's' ?> this period</p>
                        <p class="notif-text">Review outflow for unexpected drops.</p>
                        <p class="notif-time">Just now</p>
                    </div>
                </div>
                <div class="notif-item" data-notif>
                    <span class="notif-icon is-success"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                    <div class="notif-body">
                        <p class="notif-title"><?= number_format($stockInCount) ?> stock-in movement<?= $stockInCount === 1 ? '' : 's' ?></p>
                        <p class="notif-text">Inventory was replenished.</p>
                        <p class="notif-time">Recently</p>
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
            <h1 class="page-title" id="pageTitle">Stock movements</h1>
            <p class="page-subtitle">
                Every in / out / adjustment recorded against your inventory
                <span class="today-badge">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                    <?= number_format($todayCount) ?> today
                </span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn-primary-app" id="addMovementBtn">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>New movement
            </button>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Stats strip
         ========================================================== -->
    <section class="stats-strip" aria-label="Movement statistics">
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot"></span>
                <p class="stat-label">Total movements</p>
            </div>
            <p class="stat-value"><?= number_format($totalMovements) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-in"></span>
                <p class="stat-label">Stock in</p>
            </div>
            <p class="stat-value is-in"><?= number_format($stockInCount) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-out"></span>
                <p class="stat-label">Stock out</p>
            </div>
            <p class="stat-value is-out"><?= number_format($stockOutCount) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-adjustment"></span>
                <p class="stat-label">Adjustments</p>
            </div>
            <p class="stat-value is-adjustment"><?= number_format($adjustmentCount) ?></p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-today"></span>
                <p class="stat-label">Today</p>
            </div>
            <p class="stat-value is-today"><?= number_format($todayCount) ?></p>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Filter bar
         ========================================================== -->
    <section class="filter-bar" aria-label="Filter movements">
        <div class="filter-bar__group">
            <div class="search-wrap filter-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="filterSearch" class="search-input" placeholder="Filter by product, SKU or remarks..." aria-label="Filter movements" autocomplete="off">
            </div>

            <!-- Type filter — Bootstrap dropdown -->
            <div class="dropdown">
                <button type="button" class="btn-secondary" id="typeFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-inline-flex align-items-center gap-2">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span id="typeFilterLabel">All types</span>
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu" id="typeFilterMenu" aria-labelledby="typeFilterBtn">
                    <li><div class="dropdown-label-app">Movement type</div></li>
                    <li><button type="button" class="dropdown-item active" data-type="" aria-current="true"><i class="bi bi-list-ul" aria-hidden="true"></i>All types</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item" data-type="IN"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>Stock In</button></li>
                    <li><button type="button" class="dropdown-item" data-type="OUT"><i class="bi bi-box-arrow-up" aria-hidden="true"></i>Stock Out</button></li>
                    <li><button type="button" class="dropdown-item" data-type="ADJUSTMENT"><i class="bi bi-sliders" aria-hidden="true"></i>Adjustment</button></li>
                    <li><button type="button" class="dropdown-item" data-type="RETURN"><i class="bi bi-arrow-return-left" aria-hidden="true"></i>Return</button></li>
                    <li><button type="button" class="dropdown-item" data-type="DAMAGE"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i>Damage</button></li>
                </ul>
            </div>
        </div>

        <div class="filter-bar__group">
            <span class="filter-count" id="filterCount"><?= number_format($totalMovements) ?> movement<?= $totalMovements === 1 ? '' : 's' ?></span>
            <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
                <i class="bi bi-x" aria-hidden="true"></i>Clear
            </button>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Movements table
         ========================================================== -->
    <section class="section-gap">
        <div class="panel">

            <?php if (empty($movements)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></div>
                    <h3 class="empty-title">No stock movements yet</h3>
                    <p class="empty-text">Movements will appear here as soon as stock is received, sold, or adjusted.</p>
                    <button type="button" class="btn-primary-app" onclick="var b = document.getElementById('addMovementBtn'); if (b) b.click();">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>New movement
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table activity-table align-middle">
                        <caption>Stock movements: product, type, quantity, stock flow, remarks and date.</caption>
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col">Type</th>
                                <th scope="col" class="text-end">Qty</th>
                                <th scope="col">Stock flow</th>
                                <th scope="col">Remarks</th>
                                <th scope="col">When</th>
                                <th scope="col" style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody id="movementsBody">
                            <?php foreach ($movements as $m):
                                $mid      = (int) ($m['id'] ?? 0);
                                $pid      = (int) ($m['product_id'] ?? 0);
                                $pname    = (string) ($m['product_name'] ?? ($productOptions[$pid]['name'] ?? ('Product #' . $pid)));
                                $psku     = (string) ($m['sku'] ?? ($productOptions[$pid]['sku'] ?? ''));
                                $typeRaw  = (string) ($m['movement_type'] ?? '');
                                $qty      = (int) ($m['quantity'] ?? 0);
                                $before   = isset($m['previous_stock']) ? (int) $m['previous_stock'] : (isset($m['stock_before']) ? (int) $m['stock_before'] : null);
                                $after    = isset($m['new_stock'])      ? (int) $m['new_stock']      : (isset($m['stock_after'])  ? (int) $m['stock_after']  : null);
                                $remarks  = (string) ($m['remarks'] ?? '');
                                $when     = $pick($m, ['created_at', 'created_on', 'date', 'created'], '');

                                $meta     = $typeMeta($typeRaw);
                                $sign     = $meta['sign'];
                                $isPos    = ($sign === '+');
                                $isNeg    = ($sign === '−');
                                $qtyCls   = $isPos ? 'is-pos' : ($isNeg ? 'is-neg' : 'is-zero');
                                $qtyLabel = $sign . number_format(abs($qty));
                            ?>
                                <tr class="movement-row"
                                    data-movement-id="<?= $mid ?>"
                                    data-movement-type="<?= $e(strtoupper(trim($typeRaw))) ?>"
                                    data-product-name="<?= $e($pname) ?>"
                                    data-product-sku="<?= $e($psku) ?>"
                                    data-remarks="<?= $e($remarks) ?>"
                                    onclick="if (!event.target.closest('a,button,.dropdown-menu')) { window.location.href = '/stock-movements?id=<?= $mid ?>'; }"
                                >
                                    <td class="cell-product">
                                        <div class="name"><?= $e($pname) ?></div>
                                        <?php if ($psku !== ''): ?>
                                            <div class="sku"><?= $e($psku) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="type-badge <?= $e($meta['cls']) ?>">
                                            <i class="bi <?= $e($meta['icon']) ?>" aria-hidden="true"></i>
                                            <?= $e($meta['label']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="qty-delta <?= $qtyCls ?>"><?= $e($qtyLabel) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($before !== null || $after !== null): ?>
                                            <span class="flow">
                                                <span class="from"><?= $before !== null ? number_format($before) : '—' ?></span>
                                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                                <span class="to"><?= $after !== null ? number_format($after) : '—' ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="cell-when">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-remarks" title="<?= $e($remarks) ?>">
                                        <?= $remarks !== '' ? $e($remarks) : '—' ?>
                                    </td>
                                    <td class="cell-when"><?= $e($fmtWhen($when)) ?></td>
                                    <td class="cell-actions">
                                        <!-- Row actions — Bootstrap dropdown -->
                                        <div class="dropdown">
                                            <button type="button" class="icon-btn row-actions-btn" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Row actions">
                                                <i class="bi bi-three-dots" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="/stock-movements?id=<?= $mid ?>">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>View details
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-row-action="copy" data-movement-id="<?= $mid ?>">
                                                        <i class="bi bi-clipboard" aria-hidden="true"></i>Copy movement ID
                                                    </button>
                                                </li>
                                                <?php if ($pid > 0): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="/products?id=<?= $pid ?>">
                                                            <i class="bi bi-box" aria-hidden="true"></i>Open product
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="/stock-movements?product_id=<?= $pid ?>">
                                                            <i class="bi bi-list-ul" aria-hidden="true"></i>All movements for product
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

                <div class="no-results" id="noResults" role="status">No movements match your filters.</div>

                <div class="table-footer">
                    <span id="showingLabel">
                        Showing 1–<?= number_format($totalMovements) ?> of <?= number_format($totalMovements) ?>
                    </span>
                    <div class="pagination" id="pagination" aria-label="Pagination"></div>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>


<!-- ==========================================================
     COMPONENT: New movement modal
     ========================================================== -->
<div class="modal fade" id="newMovementModal" tabindex="-1" aria-labelledby="newMovementTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <form method="post" action="/stock-movements/create">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="newMovementTitle">Record stock movement</h2>
                        <p class="modal-subtitle-app">Log an in, out, adjustment, return or damage entry.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldProduct">Product</label>
                            <select class="form-select-app" id="fieldProduct" name="product_id" required>
                                <option value="">Select a product…</option>
                                <?php foreach ($productOptions as $pid => $pinfo): ?>
                                    <option value="<?= (int) $pid ?>">
                                        <?= $e($pinfo['name']) ?><?= $pinfo['sku'] !== '' ? ' · ' . $e($pinfo['sku']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($productOptions)): ?>
                                <p style="font-size:11px;color:var(--text-tertiary);margin:6px 0 0">
                                    No products available. Add a product first.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldType">Movement type</label>
                            <select class="form-select-app" id="fieldType" name="movement_type" required>
                                <option value="">Select a type…</option>
                                <option value="IN">Stock In</option>
                                <option value="OUT">Stock Out</option>
                                <option value="ADJUSTMENT">Adjustment</option>
                                <option value="RETURN">Return</option>
                                <option value="DAMAGE">Damage</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldQty">Quantity</label>
                            <input class="form-control-app" type="number" id="fieldQty" name="quantity" min="1" step="1" required placeholder="e.g. 25">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldRemarks">Remarks</label>
                            <textarea class="form-control-app" id="fieldRemarks" name="remarks" rows="3" maxlength="500" placeholder="Optional note — e.g. received from supplier, sold at POS, damaged during handling…"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app" <?= empty($productOptions) ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>>
                        <i class="bi bi-check2" aria-hidden="true"></i>Save movement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


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
    document.querySelectorAll('#userMenuBtn + .dropdown-menu [data-user-action], .dropdown-menu [data-user-action]').forEach(function (item) {
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
   NEW MOVEMENT MODAL — Bootstrap
   ========================================================= */
(function () {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
    var modalEl = document.getElementById('newMovementModal');
    var addBtn  = document.getElementById('addMovementBtn');
    if (!modalEl || !addBtn) return;
    var modal = new bootstrap.Modal(modalEl);
    addBtn.addEventListener('click', function () { modal.show(); });
})();

/* =========================================================
   MOVEMENTS TABLE — search, filter, pagination
   ========================================================= */
(function () {
    var body = document.getElementById('movementsBody');
    if (!body) return;

    var empty        = document.getElementById('noResults');
    var showing      = document.getElementById('showingLabel');
    var countEl      = document.getElementById('filterCount');
    var pagination   = document.getElementById('pagination');
    var filterSearch = document.getElementById('filterSearch');
    var topSearch    = document.getElementById('topSearch');
    var typeLabel    = document.getElementById('typeFilterLabel');
    var typeMenu     = document.getElementById('typeFilterMenu');
    var clearBtn     = document.getElementById('clearFiltersBtn');

    var PAGE_SIZE = 12;
    var currentSearch = '';
    var currentType   = '';
    var currentPage   = 1;

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr'));

    function filteredRows() {
        return allRows.filter(function (row) {
            var rowType = (row.getAttribute('data-movement-type') || '').toUpperCase();
            var typeOk  = !currentType || rowType === currentType;
            var haystack = [
                row.getAttribute('data-product-name') || '',
                row.getAttribute('data-product-sku')  || '',
                row.getAttribute('data-remarks')      || '',
                row.getAttribute('data-movement-id')  || ''
            ].join(' ').toLowerCase();
            var searchOk = !currentSearch || haystack.indexOf(currentSearch) !== -1;
            return typeOk && searchOk;
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

    function setType(type, label) {
        currentType = String(type || '').toUpperCase();
        if (typeLabel) typeLabel.textContent = label || 'All types';

        if (typeMenu) {
            typeMenu.querySelectorAll('[data-type]').forEach(function (el) {
                var on = (el.getAttribute('data-type') || '').toUpperCase() === currentType;
                el.classList.toggle('active', on);
                if (on) el.setAttribute('aria-current', 'true');
                else el.removeAttribute('aria-current');
            });
        }

        currentPage = 1;
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
        if (countEl) countEl.textContent = total + (total === 1 ? ' movement' : ' movements');
        if (clearBtn) clearBtn.hidden = !(currentSearch || currentType);

        if (showing) {
            showing.textContent = total === 0
                ? 'No movements'
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

    /* Type filter — one delegated listener on the menu (Bootstrap closes it on item click) */
    if (typeMenu) {
        typeMenu.addEventListener('click', function (e) {
            var item = e.target.closest('[data-type]');
            if (!item) return;
            setType(item.getAttribute('data-type'), item.textContent.trim());
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            setType('', 'All types');
            setSearch('');
        });
    }

    /* Row actions (Bootstrap handles menu open/close) */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-row-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-row-action');
        if (action === 'copy') {
            var id = btn.getAttribute('data-movement-id') || '';
            var msg = 'Movement #' + id;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(msg).then(function () {
                    window.showToast('Movement ID copied', 'bi-clipboard-check');
                }).catch(function () {
                    window.showToast(msg, 'bi-clipboard');
                });
            } else {
                window.showToast(msg, 'bi-clipboard');
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
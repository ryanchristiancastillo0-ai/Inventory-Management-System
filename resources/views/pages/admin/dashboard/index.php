<?php
/**
 * ADMIN DASHBOARD PAGE
 * Rendered inside app.php. Sidebar + topbar come from layout components.
 *
 * @var array $stats          KPI map (defensive — key names auto-detected)
 * @var array $registrations  Time series: [{date,count}|{label,value}|"Y-m-d"=>N]
 * @var array $recentUsers    [{id,name,email,role,status,created_at,...}, ...]
 */

$stats         = $stats         ?? [];
$registrations = $registrations ?? [];
$recentUsers   = $recentUsers   ?? [];

/* ---------- PHP 5.4+ safe helpers ---------- */
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$pickStr = static function (array $rec, array $keys, string $default = '') {
    foreach ($keys as $k) {
        if (isset($rec[$k]) && $rec[$k] !== '' && $rec[$k] !== null) return (string) $rec[$k];
    }
    return $default;
};

$pickNum = static function (array $rec, array $keys, $default = 0.0) {
    foreach ($keys as $k) {
        if (isset($rec[$k]) && is_numeric($rec[$k])) return (float) $rec[$k];
    }
    return (float) $default;
};

/* ---------- KPIs (flexible — works with any reasonable key names) ---------- */
$totalUsers    = (int) $pickNum($stats, ['total_users', 'users_total', 'totalUsers', 'users'], 0);
$activeUsers   = (int) $pickNum($stats, ['active_users', 'users_active', 'activeUsers'], 0);
$newUsers      = (int) $pickNum($stats, ['new_users', 'newUsers', 'new_registrations', 'recent_registrations', 'registrations_today'], 0);
$totalOrders   = (int) $pickNum($stats, ['total_orders', 'orders_total', 'totalOrders', 'orders'], 0);
$pendingOrders = (int) $pickNum($stats, ['pending_orders', 'pendingOrders', 'orders_pending'], 0);
$revenue       = (float) $pickNum($stats, ['revenue', 'total_revenue', 'totalRevenue', 'sales', 'total_sales'], 0);
$totalProducts = (int) $pickNum($stats, ['total_products', 'products_total', 'totalProducts'], 0);
$lowStock      = (int) $pickNum($stats, ['low_stock', 'low_stock_count', 'lowStock', 'low_stock_products'], 0);

$activePct = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0.0;
$growthPct = $totalUsers > 0 ? round(($newUsers   / $totalUsers) * 100, 1) : 0.0;

/* ---------- Normalise $registrations into a chart-ready array ---------- */
$chartData = [];
foreach ($registrations as $key => $row) {
    if (is_array($row)) {
        $label = (string) ($row['date'] ?? $row['label'] ?? $row['day'] ?? $row['month'] ?? '');
        $value = $pickNum($row, ['count', 'value', 'total', 'registrations', 'amount'], 0);
        if ($label === '') $label = '—';
        $chartData[] = ['label' => $label, 'value' => $value];
    } elseif (is_numeric($row) && is_string($key)) {
        $chartData[] = ['label' => $key, 'value' => (float) $row];
    }
}
if (count($chartData) > 14) {
    $chartData = array_slice($chartData, -14);
}

$chartMax   = 0.0;
$chartTotal = 0.0;
foreach ($chartData as $d) {
    if ($d['value'] > $chartMax) $chartMax = $d['value'];
    $chartTotal += $d['value'];
}

$fmtChartLabel = static function (string $label): string {
    if ($label === '—') return '—';
    $t = strtotime($label);
    if ($t === false) return $label;
    return date('M j', $t);
};

/* ---------- Helpers for the recent users table ---------- */
$userStatusCls = static function (string $s): string {
    switch (strtolower($s)) {
        case 'active':   return 'status-success';
        case 'inactive': return 'status-warning';
        case 'pending':  return 'status-info';
        case 'banned':
        case 'suspended':
        case 'blocked':  return 'status-danger';
        default:         return 'status-info';
    }
};

$userRoleCls = static function (string $r): string {
    switch (strtolower($r)) {
        case 'admin':
        case 'administrator':
        case 'superadmin':
        case 'super_admin': return 'is-admin';
        case 'manager':
        case 'staff':
        case 'employee':    return 'is-staff';
        default:            return 'is-user';
    }
};

$fmtWhen = static function (string $ts): string {
    if ($ts === '') return '—';
    $t = strtotime($ts);
    return $t === false ? $ts : date('M j, Y', $t);
};

$fmtRelative = static function (string $ts): string {
    if ($ts === '') return '';
    $t = strtotime($ts);
    if ($t === false) return $ts;
    $diff = time() - $t;
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $t);
};

/* ---------- Flash ---------- */
$flashSuccess = $_SESSION['success'] ?? null;
$flashError   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>


<style>
    /* ==========================================================
       ADMIN DASHBOARD — page-specific styles
       (Topbar/sidebar/dropdown/toast styles live in their components)
       ========================================================== */

    /* ---------- Page header ---------- */
    .page-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        min-width: 0;
    }
    .page-header > * { min-width: 0; }
    .page-header .page-title { margin: 0; }
    .page-header .page-subtitle {
        margin: 6px 0 0;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .date-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--info-bg);
        color: var(--info);
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }
    .date-chip i { font-size: 11px; }

    /* ---------- Stats strip (adaptive to any KPI count) ---------- */
    .stats-strip {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
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
        min-width: 0;
    }
    .stat-dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: var(--text-tertiary);
        flex-shrink: 0;
    }
    .stat-dot.is-users    { background: var(--info); }
    .stat-dot.is-active   { background: var(--success); }
    .stat-dot.is-new      { background: var(--warning); }
    .stat-dot.is-pending  { background: var(--warning); }
    .stat-dot.is-revenue  { background: var(--success); }

    .stat-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-secondary);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
    }
    .stat-value {
        font-size: 24px;
        font-weight: 600;
        letter-spacing: -0.6px;
        line-height: 1.15;
        margin: 0;
        color: var(--text);
        font-variant-numeric: tabular-nums;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .stat-hint {
        margin: 6px 0 0;
        font-size: 11px;
        color: var(--text-tertiary);
        font-variant-numeric: tabular-nums;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .stat-hint.is-up   { color: var(--success); }
    .stat-hint.is-down { color: var(--danger); }

    @media (max-width: 1199.98px) {
        .stats-strip { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .stat-item:nth-child(3n) { border-right: 0; }
        .stat-item:nth-child(n+4) { border-top: 1px solid var(--border); }
    }
    @media (max-width: 575.98px) {
        .stats-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .stat-item { border-right: 1px solid var(--border); }
        .stat-item:nth-child(3n) { border-right: 1px solid var(--border); }
        .stat-item:nth-child(2n) { border-right: 0; }
        .stat-item:nth-child(n+3) { border-top: 1px solid var(--border); }
    }

    /* ---------- Two-column premium grid ---------- */
    .dash-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
        gap: 20px;
        margin-top: 24px;
    }
    .dash-grid > * { min-width: 0; }
    @media (max-width: 991.98px) {
        .dash-grid { grid-template-columns: minmax(0, 1fr); }
    }

    /* ---------- Chart card ---------- */
    .chart-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 22px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    .chart-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--border);
        min-width: 0;
    }
    .chart-head > * { min-width: 0; }
    .chart-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
        margin: 0;
    }
    .chart-subtitle {
        font-size: 12px;
        color: var(--text-secondary);
        margin: 4px 0 0;
    }
    .chart-total { text-align: right; }
    .chart-total-value {
        display: block;
        font-size: 22px;
        font-weight: 600;
        letter-spacing: -0.5px;
        color: var(--text);
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
    }
    .chart-total-label {
        display: block;
        font-size: 11px;
        color: var(--text-tertiary);
        margin-top: 2px;
    }

    /* ---------- Line chart ---------- */
    .chart-body {
        flex: 1;
        position: relative;
        height: 200px;
        min-width: 0;
        margin-top: 8px;
    }
    /* Plot area — inset so edge points + labels never get clipped */
    .chart-plot {
        position: absolute;
        top: 24px;
        right: 12px;
        bottom: 0;
        left: 12px;
    }
    .chart-svg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        overflow: visible;
    }
    .chart-grid {
        stroke: var(--border);
        stroke-width: 1;
        stroke-dasharray: 3 4;
        vector-effect: non-scaling-stroke;
    }
    .chart-area { stroke: none; }
    .chart-line {
        fill: none;
        stroke: var(--info);
        stroke-width: 2;
        stroke-linejoin: round;
        stroke-linecap: round;
        vector-effect: non-scaling-stroke;
    }

    .chart-point {
        position: absolute;
        width: 16px;
        height: 16px;
        transform: translate(-50%, 50%);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: default;
        z-index: 1;
    }
    .chart-point::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--surface);
        border: 2px solid var(--info);
        transition: transform 140ms ease, background-color 140ms ease;
    }
    .chart-point:hover,
    .chart-point:focus { z-index: 3; outline: none; }
    .chart-point:hover::before,
    .chart-point:focus::before {
        background: var(--info);
        transform: scale(1.35);
    }
    .chart-point__tip {
        position: absolute;
        bottom: calc(100% + 4px);
        left: 50%;
        transform: translateX(-50%) translateY(4px);
        padding: 5px 9px;
        border-radius: 6px;
        background: var(--text);
        color: var(--surface);
        font-size: 11px;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 120ms ease, transform 120ms ease;
    }
    .chart-point:hover .chart-point__tip,
    .chart-point:focus .chart-point__tip {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    .chart-xlabel {
        position: absolute;
        bottom: -22px;
        transform: translateX(-50%);
        font-size: 9px;
        color: var(--text-tertiary);
        white-space: nowrap;
        pointer-events: none;
    }

    .chart-footer {
        margin-top: 32px;
        padding-top: 14px;
        border-top: 1px solid var(--border);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        font-size: 11px;
        color: var(--text-tertiary);
        min-width: 0;
    }
    .chart-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 200px;
        color: var(--text-tertiary);
        font-size: 12px;
    }

    /* ---------- Quick actions ---------- */
    .qa-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 22px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        min-width: 0;
    }
    .qa-head {
        padding-bottom: 14px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 14px;
    }
    .qa-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--text);
        margin: 0;
    }
    .qa-subtitle {
        font-size: 12px;
        color: var(--text-secondary);
        margin: 4px 0 0;
    }
    .qa-list { display: flex; flex-direction: column; gap: 4px; }
    .qa-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 10px;
        border-radius: 8px;
        text-decoration: none;
        color: var(--text);
        transition: background-color 120ms ease;
        min-width: 0;
    }
    .qa-item:hover { background: var(--surface-muted); color: var(--text); }
    .qa-icon {
        width: 34px; height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
        background: var(--surface-hover);
        color: var(--text-secondary);
    }
    .qa-icon.is-primary { background: var(--info-bg);    color: var(--info); }
    .qa-icon.is-success { background: var(--success-bg); color: var(--success); }
    .qa-icon.is-warning { background: var(--warning-bg); color: var(--warning); }
    .qa-icon.is-danger  { background: var(--danger-bg);  color: var(--danger); }

    .qa-body { min-width: 0; flex: 1; }
    .qa-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .qa-desc {
        font-size: 11px;
        color: var(--text-tertiary);
        margin: 2px 0 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .qa-chev { color: var(--text-tertiary); font-size: 12px; flex-shrink: 0; }

    /* ---------- Filter bar (bulletproof) ---------- */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 24px;
        min-width: 0;
    }
    .filter-bar__group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        min-width: 0;
    }
    .filter-search {
        width: 280px;
        max-width: 100%;
        flex: 0 1 280px;
        min-width: 0;
    }
    .filter-count {
        font-size: 12px;
        color: var(--text-tertiary);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    @media (max-width: 767.98px) {
        .filter-search { flex: 1 1 100%; width: 100%; }
    }

    /* Page-only dropdown helpers (dropdown-menu base styles come from topbar) */
    .dropdown-check {
        margin-left: auto;
        font-size: 11px;
        color: var(--success);
    }
    #roleFilterBtn,
    #statusFilterBtn { min-width: 150px; justify-content: space-between; }
    @media (max-width: 575.98px) {
        #roleFilterBtn,
        #statusFilterBtn { flex: 1 1 auto; min-width: 0; }
    }

    /* ---------- Users table ---------- */
    .user-row { transition: background-color 120ms ease; }
    .user-row:hover { background: var(--surface-muted); }

    .user-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .user-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--surface-hover), var(--surface-muted));
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        flex-shrink: 0;
        border: 1px solid var(--border);
    }
    .user-meta { min-width: 0; flex: 1; }
    .user-name {
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .user-email {
        font-size: 11px;
        color: var(--text-tertiary);
        margin: 1px 0 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
        white-space: nowrap;
    }
    .role-badge i { font-size: 10px; }
    .role-badge.is-admin { background: var(--info-bg);    color: var(--info); }
    .role-badge.is-staff { background: var(--warning-bg); color: var(--warning); }
    .role-badge.is-user  { background: var(--surface-muted); color: var(--text-secondary); }

    .cell-when {
        font-size: 12px;
        color: var(--text-secondary);
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .cell-when .rel { color: var(--text-tertiary); font-size: 11px; }
    .cell-actions { width: 44px; text-align: right; }
    .row-actions-btn { width: 28px; height: 28px; font-size: 14px; }

    /* ---------- Empty state ---------- */
    .empty-state { padding: 60px 24px; text-align: center; }
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
</style>


<main class="dashboard-content">

    <!-- ==========================================================
         COMPONENT: Page header
         ========================================================== -->
    <section class="page-header" aria-labelledby="pageTitle">
        <div class="min-w-0">
            <h1 class="page-title" id="pageTitle">Welcome back, Administrator</h1>
            <p class="page-subtitle">
                Here's what's happening across your store today
                <span class="date-chip">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <?= date('l, F j, Y') ?>
                </span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="/admin/users" class="btn-secondary">
                <i class="bi bi-people" aria-hidden="true"></i>Manage users
            </a>
            <a href="/admin/activity-logs" class="btn-primary-app">
                <i class="bi bi-activity" aria-hidden="true"></i>Activity logs
            </a>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Stats strip (5 KPIs)
         ========================================================== -->
    <section class="stats-strip" aria-label="Dashboard statistics">
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-users"></span>
                <p class="stat-label">Total users</p>
            </div>
            <p class="stat-value"><?= number_format($totalUsers) ?></p>
            <p class="stat-hint"><?= number_format($activeUsers) ?> active</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-active"></span>
                <p class="stat-label">Active users</p>
            </div>
            <p class="stat-value"><?= number_format($activeUsers) ?></p>
            <p class="stat-hint <?= $activePct >= 50 ? 'is-up' : 'is-down' ?>">
                <?= number_format($activePct, 1) ?>% of total
            </p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-new"></span>
                <p class="stat-label">New registrations</p>
            </div>
            <p class="stat-value"><?= number_format($newUsers) ?></p>
            <p class="stat-hint <?= $growthPct > 0 ? 'is-up' : '' ?>">
                +<?= number_format($growthPct, 1) ?>% growth
            </p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-pending"></span>
                <p class="stat-label">Pending orders</p>
            </div>
            <p class="stat-value"><?= number_format($pendingOrders) ?></p>
            <p class="stat-hint"><?= number_format($totalOrders) ?> total orders</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-revenue"></span>
                <p class="stat-label">Revenue</p>
            </div>
            <p class="stat-value">₱<?= number_format($revenue, 2) ?></p>
            <p class="stat-hint">
                <?= $totalProducts > 0 ? number_format($totalProducts) . ' products · ' . number_format($lowStock) . ' low stock' : 'All-time total' ?>
            </p>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Premium grid (line chart + quick actions)
         ========================================================== -->
    <section class="dash-grid" aria-label="Overview charts and actions">

        <div class="chart-card">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Registrations</h3>
                    <p class="chart-subtitle">New user sign-ups over the last <?= count($chartData) ?> day<?= count($chartData) === 1 ? '' : 's' ?></p>
                </div>
                <div class="chart-total">
                    <span class="chart-total-value"><?= number_format($chartTotal) ?></span>
                    <span class="chart-total-label">total registrations</span>
                </div>
            </div>

            <?php if (empty($chartData)): ?>
                <div class="chart-empty">No registration data available for this period.</div>
            <?php else:
                /* Line chart geometry — x/y in % of the plot area (SVG viewBox is 0–100) */
                $n   = count($chartData);
                $pts = [];
                foreach ($chartData as $i => $d) {
                    $x = $n > 1 ? ($i / ($n - 1)) * 100 : 50;
                    $y = $chartMax > 0 ? 100 - (($d['value'] / $chartMax) * 100) : 100;
                    $pts[] = [
                        'x' => number_format($x, 3, '.', ''),
                        'y' => number_format($y, 3, '.', ''),
                    ];
                }
                $linePts = [];
                foreach ($pts as $p) { $linePts[] = $p['x'] . ',' . $p['y']; }
                $linePts = implode(' ', $linePts);
                $areaPts = $pts[0]['x'] . ',100 ' . $linePts . ' ' . $pts[$n - 1]['x'] . ',100';
            ?>
                <div class="chart-body">
                    <div class="chart-plot">

                        <svg class="chart-svg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                            <defs>
                                <linearGradient id="regAreaFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%"   style="stop-color:var(--info);stop-opacity:0.22"></stop>
                                    <stop offset="100%" style="stop-color:var(--info);stop-opacity:0"></stop>
                                </linearGradient>
                            </defs>
                            <line class="chart-grid" x1="0" x2="100" y1="0"   y2="0"></line>
                            <line class="chart-grid" x1="0" x2="100" y1="25"  y2="25"></line>
                            <line class="chart-grid" x1="0" x2="100" y1="50"  y2="50"></line>
                            <line class="chart-grid" x1="0" x2="100" y1="75"  y2="75"></line>
                            <line class="chart-grid" x1="0" x2="100" y1="100" y2="100"></line>
                            <polygon  class="chart-area" points="<?= $e($areaPts) ?>" fill="url(#regAreaFill)"></polygon>
                            <polyline class="chart-line" points="<?= $e($linePts) ?>"></polyline>
                        </svg>

                        <?php foreach ($chartData as $i => $d):
                            $label     = $fmtChartLabel((string) $d['label']);
                            $val       = (int) round($d['value']);
                            $tipText   = $label . ' · ' . number_format($val) . ' registration' . ($val === 1 ? '' : 's');
                            $showLabel = ($i % 2 === 0) || ($i === $n - 1);
                            $bottomPct = number_format(100 - (float) $pts[$i]['y'], 3, '.', '');
                        ?>
                            <span class="chart-point"
                                  style="left:<?= $pts[$i]['x'] ?>%;bottom:<?= $bottomPct ?>%"
                                  tabindex="0"
                                  role="img"
                                  aria-label="<?= $e($tipText) ?>">
                                <span class="chart-point__tip"><?= $e($tipText) ?></span>
                            </span>
                            <?php if ($showLabel): ?>
                                <span class="chart-xlabel" style="left:<?= $pts[$i]['x'] ?>%"><?= $e($label) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>

                    </div>
                </div>
            <?php endif; ?>

            <div class="chart-footer">
                <span>
                    <?php if (!empty($chartData)):
                        $first = $fmtChartLabel((string) $chartData[0]['label']);
                        $last  = $fmtChartLabel((string) $chartData[count($chartData) - 1]['label']);
                    ?>
                        <?= $e($first) ?> – <?= $e($last) ?>
                    <?php else: ?>
                        Awaiting data
                    <?php endif; ?>
                </span>
                <span>Hover a point for details</span>
            </div>
        </div>

        <div class="qa-card">
            <div class="qa-head">
                <h3 class="qa-title">Quick actions</h3>
                <p class="qa-subtitle">Jump to the most-used admin tools</p>
            </div>
            <div class="qa-list">
                <a class="qa-item" href="/admin/users">
                    <span class="qa-icon is-primary"><i class="bi bi-person-plus" aria-hidden="true"></i></span>
                    <div class="qa-body">
                        <p class="qa-label">Add new user</p>
                        <p class="qa-desc">Invite a staff member or customer</p>
                    </div>
                    <i class="bi bi-chevron-right qa-chev" aria-hidden="true"></i>
                </a>
                <a class="qa-item" href="/admin/reports">
                    <span class="qa-icon is-success"><i class="bi bi-journal" aria-hidden="true"></i></span>
                    <div class="qa-body">
                        <p class="qa-label">Review reports</p>
                        <p class="qa-desc">Handle user feedback and issues</p>
                    </div>
                    <i class="bi bi-chevron-right qa-chev" aria-hidden="true"></i>
                </a>
                <a class="qa-item" href="/admin/activity-logs">
                    <span class="qa-icon is-warning"><i class="bi bi-activity" aria-hidden="true"></i></span>
                    <div class="qa-body">
                        <p class="qa-label">Activity logs</p>
                        <p class="qa-desc">Audit recent system events</p>
                    </div>
                    <i class="bi bi-chevron-right qa-chev" aria-hidden="true"></i>
                </a>
                <a class="qa-item" href="/settings">
                    <span class="qa-icon is-danger"><i class="bi bi-gear" aria-hidden="true"></i></span>
                    <div class="qa-body">
                        <p class="qa-label">System settings</p>
                        <p class="qa-desc">Tune preferences and account</p>
                    </div>
                    <i class="bi bi-chevron-right qa-chev" aria-hidden="true"></i>
                </a>
            </div>
        </div>

    </section>

    <!-- ==========================================================
         COMPONENT: Recent users — filter bar
         ========================================================== -->
    <section class="filter-bar" aria-label="Filter recent users">
        <div class="filter-bar__group">
            <div class="search-wrap filter-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="filterSearch" class="search-input" placeholder="Filter by name, email or role..." aria-label="Filter users" autocomplete="off">
            </div>

            <!-- Role filter — Bootstrap dropdown -->
            <div class="dropdown">
                <button type="button" class="btn-secondary" id="roleFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-inline-flex align-items-center gap-2">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                        <span id="roleFilterLabel">All roles</span>
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="roleFilterBtn">
                    <li><div class="dropdown-label-app">Role</div></li>
                    <li><button type="button" class="dropdown-item active" data-role=""><i class="bi bi-list-ul" aria-hidden="true"></i>All roles<i class="bi bi-check2 dropdown-check"></i></button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item" data-role="admin"><i class="bi bi-shield-lock" aria-hidden="true"></i>Admin</button></li>
                    <li><button type="button" class="dropdown-item" data-role="staff"><i class="bi bi-person-badge" aria-hidden="true"></i>Staff</button></li>
                    <li><button type="button" class="dropdown-item" data-role="user"><i class="bi bi-person" aria-hidden="true"></i>Customer</button></li>
                </ul>
            </div>

            <!-- Status filter — Bootstrap dropdown -->
            <div class="dropdown">
                <button type="button" class="btn-secondary" id="statusFilterBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-inline-flex align-items-center gap-2">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span id="statusFilterLabel">All statuses</span>
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="statusFilterBtn">
                    <li><div class="dropdown-label-app">Status</div></li>
                    <li><button type="button" class="dropdown-item active" data-status=""><i class="bi bi-list-ul" aria-hidden="true"></i>All statuses<i class="bi bi-check2 dropdown-check"></i></button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item" data-status="active"><i class="bi bi-check-circle" aria-hidden="true"></i>Active</button></li>
                    <li><button type="button" class="dropdown-item" data-status="inactive"><i class="bi bi-pause-circle" aria-hidden="true"></i>Inactive</button></li>
                    <li><button type="button" class="dropdown-item" data-status="pending"><i class="bi bi-hourglass-split" aria-hidden="true"></i>Pending</button></li>
                    <li><button type="button" class="dropdown-item" data-status="suspended"><i class="bi bi-slash-circle" aria-hidden="true"></i>Suspended</button></li>
                </ul>
            </div>
        </div>

        <div class="filter-bar__group">
            <span class="filter-count" id="filterCount"><?= number_format(count($recentUsers)) ?> user<?= count($recentUsers) === 1 ? '' : 's' ?></span>
            <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
                <i class="bi bi-x" aria-hidden="true"></i>Clear
            </button>
        </div>
    </section>

    <!-- ==========================================================
         COMPONENT: Recent users table
         ========================================================== -->
    <section class="section-gap">
        <div class="panel">

            <?php if (empty($recentUsers)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                    <h3 class="empty-title">No users yet</h3>
                    <p class="empty-text">Newly registered users will appear here as they sign up.</p>
                    <a href="/admin/users" class="btn-primary-app">
                        <i class="bi bi-person-plus" aria-hidden="true"></i>Add first user
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table activity-table align-middle">
                        <caption>Recent users: name, role, status and registration date.</caption>
                        <thead>
                            <tr>
                                <th scope="col">User</th>
                                <th scope="col">Role</th>
                                <th scope="col">Status</th>
                                <th scope="col">Registered</th>
                                <th scope="col" style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody id="usersBody">
                            <?php foreach ($recentUsers as $u):
                                $uid     = (int) ($u['id'] ?? 0);
                                $uname   = $pickStr($u, ['name', 'full_name', 'fullName', 'username'], 'User #' . $uid);
                                $uemail  = $pickStr($u, ['email', 'email_address', 'mail'], '');
                                $urole   = $pickStr($u, ['role', 'role_name', 'type', 'user_role'], 'user');
                                $ustatus = $pickStr($u, ['status', 'state', 'account_status'], 'active');
                                $ujoin   = $pickStr($u, ['created_at', 'created_on', 'joined_at', 'registered_at', 'date_joined'], '');

                                $initial  = strtoupper(substr($uname !== '' ? $uname : '?', 0, 1));
                                $roleCls  = $userRoleCls($urole);
                                $statCls  = $userStatusCls($ustatus);
                            ?>
                                <tr class="user-row"
                                    data-user-id="<?= $uid ?>"
                                    data-role="<?= $e(strtolower($urole)) ?>"
                                    data-status="<?= $e(strtolower($ustatus)) ?>"
                                    data-name="<?= $e($uname) ?>"
                                    data-email="<?= $e($uemail) ?>"
                                >
                                    <td>
                                        <div class="user-cell">
                                            <span class="user-avatar" aria-hidden="true"><?= $e($initial) ?></span>
                                            <div class="user-meta">
                                                <p class="user-name"><?= $e($uname) ?></p>
                                                <?php if ($uemail !== ''): ?>
                                                    <p class="user-email"><?= $e($uemail) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge <?= $e($roleCls) ?>">
                                            <i class="bi bi-circle-fill" aria-hidden="true"></i>
                                            <?= $e($urole) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status <?= $e($statCls) ?>"><?= $e($ustatus) ?></span>
                                    </td>
                                    <td class="cell-when">
                                        <?= $e($fmtWhen($ujoin)) ?>
                                        <?php if ($ujoin !== ''): ?>
                                            <div class="rel"><?= $e($fmtRelative($ujoin)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-actions">
                                        <div class="dropdown">
                                            <button type="button" class="icon-btn row-actions-btn" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Row actions">
                                                <i class="bi bi-three-dots" aria-hidden="true"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="/admin/users">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>View user
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="/admin/users">
                                                        <i class="bi bi-pencil" aria-hidden="true"></i>Edit user
                                                    </a>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-row-action="copy-email" data-email="<?= $e($uemail) ?>">
                                                        <i class="bi bi-clipboard" aria-hidden="true"></i>Copy email
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="no-results" id="noResults" role="status">No users match your filters.</div>

                <div class="table-footer">
                    <span id="showingLabel">
                        Showing 1–<?= number_format(count($recentUsers)) ?> of <?= number_format(count($recentUsers)) ?>
                    </span>
                    <div class="pagination" id="pagination" aria-label="Pagination"></div>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>


<script>
/* =========================================================
   FLASH MESSAGES (window.showToast is provided by topbar)
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
   RECENT USERS — search + role + status filter + pagination
   ========================================================= */
(function () {
    var body = document.getElementById('usersBody');
    if (!body) return;

    var empty        = document.getElementById('noResults');
    var showing      = document.getElementById('showingLabel');
    var countEl      = document.getElementById('filterCount');
    var pagination   = document.getElementById('pagination');
    var filterSearch = document.getElementById('filterSearch');
    var clearBtn     = document.getElementById('clearFiltersBtn');

    var roleLabel    = document.getElementById('roleFilterLabel');
    var statusLabel  = document.getElementById('statusFilterLabel');

    var PAGE_SIZE = 10;
    var currentSearch = '';
    var currentRole   = '';
    var currentStatus = '';
    var currentPage   = 1;

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr'));

    function filteredRows() {
        return allRows.filter(function (row) {
            var roleOk   = !currentRole   || row.getAttribute('data-role')   === currentRole;
            var statusOk = !currentStatus || row.getAttribute('data-status') === currentStatus;
            if (!roleOk || !statusOk) return false;
            if (!currentSearch) return true;
            var haystack = [
                row.getAttribute('data-name')  || '',
                row.getAttribute('data-email') || '',
                row.getAttribute('data-role')  || '',
                row.getAttribute('data-status') || ''
            ].join(' ').toLowerCase();
            return haystack.indexOf(currentSearch) !== -1;
        });
    }

    function syncInputs(value) {
        if (filterSearch && filterSearch.value !== value) filterSearch.value = value;
        var tb = document.getElementById('topbarSearch');
        if (tb && tb.value !== value) tb.value = value; /* no 'input' event is fired */
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
        if (countEl) countEl.textContent = total + (total === 1 ? ' user' : ' users');
        if (clearBtn) clearBtn.hidden = !(currentSearch || currentRole || currentStatus);

        if (showing) {
            showing.textContent = total === 0
                ? 'No users'
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

    /* In-page search */
    if (filterSearch) filterSearch.addEventListener('input', function () { setSearch(filterSearch.value); });

    /* Topbar search — topbar dispatches this event on every keystroke */
    document.addEventListener('topbar:search', function (e) {
        if (!e.detail) return;
        setSearch(e.detail.value);
    });

    /* Role filter — button-click (Bootstrap handles open/close) */
    document.querySelectorAll('[data-role]').forEach(function (item) {
        item.addEventListener('click', function () {
            currentRole = item.getAttribute('data-role') || '';
            if (roleLabel) roleLabel.textContent = item.textContent.trim();
            document.querySelectorAll('[data-role]').forEach(function (x) {
                x.classList.toggle('active', x === item);
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
                if (x === item && currentRole !== '') {
                    var i = document.createElement('i');
                    i.className = 'bi bi-check2 dropdown-check';
                    x.appendChild(i);
                }
            });
            currentPage = 1;
            render();
        });
    });

    /* Status filter */
    document.querySelectorAll('[data-status]').forEach(function (item) {
        item.addEventListener('click', function () {
            currentStatus = item.getAttribute('data-status') || '';
            if (statusLabel) statusLabel.textContent = item.textContent.trim();
            document.querySelectorAll('[data-status]').forEach(function (x) {
                x.classList.toggle('active', x === item);
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
                if (x === item && currentStatus !== '') {
                    var i = document.createElement('i');
                    i.className = 'bi bi-check2 dropdown-check';
                    x.appendChild(i);
                }
            });
            currentPage = 1;
            render();
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            currentRole = '';
            currentStatus = '';
            if (roleLabel)   roleLabel.textContent = 'All roles';
            if (statusLabel) statusLabel.textContent = 'All statuses';
            document.querySelectorAll('[data-role], [data-status]').forEach(function (x) {
                x.classList.remove('active');
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
            });
            setSearch('');
        });
    }

    /* Row actions */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-row-action]');
        if (!btn) return;
        if (btn.getAttribute('data-row-action') === 'copy-email') {
            var email = btn.getAttribute('data-email') || '';
            if (!email) { window.showToast('No email on file', 'bi-envelope-slash'); return; }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(email).then(function () {
                    window.showToast('Email copied', 'bi-clipboard-check');
                }).catch(function () {
                    window.showToast(email, 'bi-clipboard');
                });
            } else {
                window.showToast(email, 'bi-clipboard');
            }
        }
    });

    /* ⌘K / Ctrl+K — topbar already focuses #topbarSearch; page then focuses its own filter */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && String(e.key).toLowerCase() === 'k') {
            e.preventDefault();
            if (filterSearch) filterSearch.focus();
        }
    });

    render();
})();
</script>
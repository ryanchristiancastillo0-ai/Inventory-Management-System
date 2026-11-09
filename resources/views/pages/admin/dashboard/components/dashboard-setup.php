<?php
/**
 * ADMIN DASHBOARD SETUP
 * Variable defaults, KPI normalisation and helpers.
 * Required first by ../index.php.
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

<?php
/**
 * ADMIN USERS SETUP
 * Variable defaults, KPI extraction and helpers.
 * Required first by ../index.php.
 *
 * @var array       $users       List of user records
 * @var array       $stats       KPI map (auto-detected keys)
 * @var array|null  $flash       ['type' => 'success'|'danger', 'message' => string]
 * @var string      $csrfToken
 */

$users     = $users     ?? [];
$stats     = $stats     ?? [];
$flash     = $flash     ?? null;
$csrfToken = $csrfToken ?? '';

/* ---------- Safe helpers ---------- */
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$pickStr = static function (array $rec, array $keys, string $default = '') {
    foreach ($keys as $k) {
        if (isset($rec[$k]) && $rec[$k] !== '' && $rec[$k] !== null) return (string) $rec[$k];
    }
    return $default;
};

$pickNum = static function (array $rec, array $keys, $default = 0) {
    foreach ($keys as $k) {
        if (isset($rec[$k]) && is_numeric($rec[$k])) return (int) $rec[$k];
    }
    return (int) $default;
};

/* ---------- KPI extraction (defensive) ---------- */
$totalUsers   = $pickNum($stats, ['total', 'total_users', 'users_count', 'totalUsers'], 0);
$adminCount   = $pickNum($stats, ['admins', 'admin_count', 'adminCount', 'total_admins'], 0);
$regularCount = $pickNum($stats, ['regular', 'users', 'user_count', 'regular_users'], 0);
$newThisMonth = $pickNum($stats, ['new', 'new_users', 'recent', 'this_month', 'new_this_month'], 0);

/* Compute from $users if stats came back empty */
if ($totalUsers === 0 && !empty($users)) {
    $totalUsers   = count($users);
    $adminCount   = 0;
    $regularCount = 0;
    $newThisMonth = 0;
    $nowMonth     = date('Y-m');
    foreach ($users as $u) {
        $r = strtoupper((string) ($u['role'] ?? ''));
        if ($r === 'ADMIN') $adminCount++; else $regularCount++;
        $created = (string) ($u['created_at'] ?? $u['created_on'] ?? '');
        if ($created !== '' && strpos($created, $nowMonth) === 0) $newThisMonth++;
    }
}

/* ---------- Formatting helpers ---------- */
$fmtWhen = static function (string $ts) {
    if ($ts === '') return '—';
    $t = strtotime($ts);
    return $t === false ? $ts : date('M j, Y', $t);
};

$fmtRelative = static function (string $ts) {
    if ($ts === '') return '';
    $t = strtotime($ts);
    if ($t === false) return '';
    $diff = time() - $t;
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $t);
};

$roleKey = static function (string $role): string {
    $r = strtoupper(trim($role));
    return $r === 'ADMIN' ? 'admin' : 'user';
};

$roleLabel = static function (string $role): string {
    $r = strtoupper(trim($role));
    if ($r === 'ADMIN') return 'Admin';
    if ($r === 'USER')  return 'User';
    return $r === '' ? 'User' : ucfirst(strtolower($r));
};

/* ---------- Flash ---------- */
$flashType = (string) ($flash['type'] ?? '');
$flashMsg  = (string) ($flash['message'] ?? '');

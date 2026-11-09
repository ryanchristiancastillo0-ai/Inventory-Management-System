<?php
/**
 * ADMIN ACTIVITY LOGS SETUP
 * Variable defaults, KPI extraction and helpers.
 * Required first by ../index.php.
 *
 * @var array $logs
 * @var array $stats
 * @var int   $currentPage
 * @var int   $totalPages
 * @var int   $totalLogs
 */

$logs        = $logs        ?? [];
$stats       = $stats       ?? [];
$currentPage = (int) ($currentPage ?? 1);
$totalPages  = (int) ($totalPages  ?? 1);
$totalLogs   = (int) ($totalLogs   ?? 0);

if ($currentPage < 1) $currentPage = 1;
if ($totalPages  < 1) $totalPages  = 1;

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

/* ---------- Action badge registry ---------- */
$actionBadge = static function (string $action): array {
    $a = strtolower(trim($action));
    if ($a === '') return ['label' => '—', 'cls' => 'is-other', 'icon' => 'bi-dot'];

    if (strpos($a, 'create') !== false || strpos($a, 'add') === 0) {
        return ['label' => ucfirst($a), 'cls' => 'is-create', 'icon' => 'bi-plus-circle'];
    }
    if (strpos($a, 'update') !== false || strpos($a, 'edit') !== false || strpos($a, 'modif') !== false) {
        return ['label' => ucfirst($a), 'cls' => 'is-update', 'icon' => 'bi-pencil-square'];
    }
    if (strpos($a, 'delete') !== false || strpos($a, 'remove') !== false) {
        return ['label' => ucfirst($a), 'cls' => 'is-delete', 'icon' => 'bi-trash'];
    }
    if (strpos($a, 'logout') !== false || strpos($a, 'signout') !== false || strpos($a, 'sign_out') !== false) {
        return ['label' => ucfirst($a), 'cls' => 'is-logout', 'icon' => 'bi-box-arrow-right'];
    }
    if (strpos($a, 'login') !== false || strpos($a, 'signin') !== false || strpos($a, 'sign_in') !== false) {
        return ['label' => ucfirst($a), 'cls' => 'is-login', 'icon' => 'bi-box-arrow-in-right'];
    }
    if (strpos($a, 'view') !== false || strpos($a, 'read') !== false) {
        return ['label' => ucfirst($a), 'cls' => 'is-view', 'icon' => 'bi-eye'];
    }
    return ['label' => ucfirst($a), 'cls' => 'is-other', 'icon' => 'bi-circle'];
};

$categorize = static function (string $action): string {
    $a = strtolower(trim($action));
    if ($a === '') return 'other';
    if (strpos($a, 'create') !== false || strpos($a, 'add') === 0) return 'create';
    if (strpos($a, 'update') !== false || strpos($a, 'edit') !== false) return 'update';
    if (strpos($a, 'delete') !== false || strpos($a, 'remove') !== false) return 'delete';
    if (strpos($a, 'logout') !== false || strpos($a, 'signout') !== false) return 'logout';
    if (strpos($a, 'login') !== false || strpos($a, 'signin') !== false) return 'login';
    if (strpos($a, 'view') !== false || strpos($a, 'read') !== false) return 'view';
    return 'other';
};

/* ---------- Time helpers ---------- */
$fmtWhen = static function (string $ts) {
    if ($ts === '') return '—';
    $t = strtotime($ts);
    return $t === false ? $ts : date('M j, Y · g:i A', $t);
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

/* ---------- KPI extraction ---------- */
$totalActivities = $pickNum($stats, ['total', 'total_logs', 'total_activities', 'logs_count'], $totalLogs);
$loginCount      = $pickNum($stats, ['logins', 'login_count', 'total_logins'], 0);
$changeCount     = $pickNum($stats, ['changes', 'data_changes', 'total_changes', 'mutations'], 0);
$todayCount      = $pickNum($stats, ['today', 'today_count', 'actions_today', 'today_logs'], 0);
$uniqueUsers     = $pickNum($stats, ['unique_users', 'unique_actors', 'users_count', 'actors'], 0);

/* Fallback: approximate from current page if stats empty */
if (empty($stats) && !empty($logs)) {
    $loginCount  = 0;
    $changeCount = 0;
    $todayCount  = 0;
    $uniqueSet   = [];
    $today       = date('Y-m-d');

    foreach ($logs as $log) {
        $raw    = (string) ($log['action'] ?? $log['action_type'] ?? '');
        $cat    = $categorize($raw);
        if ($cat === 'login')  $loginCount++;
        if (in_array($cat, ['create', 'update', 'delete'], true)) $changeCount++;
        $when = (string) ($log['created_at'] ?? $log['created_on'] ?? '');
        if ($when !== '' && strpos($when, $today) === 0) $todayCount++;
        $uid = (string) ($log['user_id'] ?? $log['actor_id'] ?? $log['admin_id'] ?? '');
        if ($uid !== '' && !isset($uniqueSet[$uid])) { $uniqueSet[$uid] = true; $uniqueUsers++; }
    }
    $totalActivities = $totalLogs;
}

/* ---------- Pagination range builder (server-side) ---------- */
$buildRange = static function (int $cur, int $total): array {
    $range = [];
    if ($total <= 7) {
        for ($i = 1; $i <= $total; $i++) $range[] = $i;
    } else {
        $range[] = 1;
        if ($cur > 3) $range[] = '…';
        for ($j = max(2, $cur - 1); $j <= min($total - 1, $cur + 1); $j++) $range[] = $j;
        if ($cur < $total - 2) $range[] = '…';
        $range[] = $total;
    }
    return $range;
};
$pageRange = $buildRange($currentPage, $totalPages);

/* ---------- Flash (from session — this controller has none) ---------- */
$flashSuccess = $_SESSION['success'] ?? null;
$flashError   = $_SESSION['error']   ?? null;
$flashAdmin   = $_SESSION['admin_user_flash'] ?? null;
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['admin_user_flash']);

if (is_array($flashAdmin)) {
    $t = (string) ($flashAdmin['type'] ?? '');
    $m = (string) ($flashAdmin['message'] ?? '');
    if ($t === 'danger') $flashError = $m ?: $flashError;
    else                 $flashSuccess = $m ?: $flashSuccess;
}

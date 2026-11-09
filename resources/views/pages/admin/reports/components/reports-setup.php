<?php
/**
 * ADMIN REPORTS SETUP
 * Variable defaults, helper closures and KPI extraction.
 * Required first by ../index.php.
 *
 * @var array       $reports
 * @var array       $stats
 * @var array|null  $flash       ['type' => 'success'|'danger', 'message' => string]
 * @var string      $csrfToken
 */

$reports   = $reports   ?? [];
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

/* ---------- Status registry ---------- */
$statusMeta = static function (string $s): array {
    $u = strtoupper(trim($s));
    switch ($u) {
        case 'PENDING':
        case 'OPEN':
        case 'NEW':
            return ['label' => 'Pending',     'cls' => 'is-pending',     'icon' => 'bi-hourglass-split',      'key' => 'pending'];
        case 'IN_PROGRESS':
        case 'IN PROGRESS':
        case 'REVIEWING':
        case 'PROCESSING':
            return ['label' => 'In progress', 'cls' => 'is-progress',    'icon' => 'bi-arrow-repeat',         'key' => 'progress'];
        case 'RESOLVED':
        case 'CLOSED':
        case 'DONE':
        case 'COMPLETED':
            return ['label' => 'Resolved',    'cls' => 'is-resolved',    'icon' => 'bi-check2-circle',        'key' => 'resolved'];
        case 'DISMISSED':
        case 'REJECTED':
        case 'IGNORED':
            return ['label' => 'Dismissed',   'cls' => 'is-dismissed',   'icon' => 'bi-slash-circle',         'key' => 'dismissed'];
        default:
            return ['label' => $u !== '' ? ucfirst(strtolower($u)) : 'Unknown',
                    'cls'   => 'is-other',    'icon' => 'bi-circle',    'key' => 'other'];
    }
};

$priorityMeta = static function (string $p): array {
    $u = strtoupper(trim($p));
    switch ($u) {
        case 'URGENT':
        case 'CRITICAL':
        case 'HIGH':
            return ['label' => ucfirst(strtolower($u)), 'cls' => 'is-high'];
        case 'MEDIUM':
        case 'NORMAL':
            return ['label' => 'Medium', 'cls' => 'is-medium'];
        case 'LOW':
        case 'MINOR':
            return ['label' => 'Low',    'cls' => 'is-low'];
        default:
            return ['label' => $u !== '' ? ucfirst(strtolower($u)) : 'Normal', 'cls' => 'is-medium'];
    }
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

/* ---------- KPI extraction (defensive) ---------- */
$totalReports   = $pickNum($stats, ['total', 'total_reports', 'reports_count', 'totalReports'], 0);
$pendingReports = $pickNum($stats, ['pending', 'pending_reports', 'open_reports', 'open'], 0);
$progressCount  = $pickNum($stats, ['in_progress', 'progress', 'reviewing', 'processing'], 0);
$resolvedCount  = $pickNum($stats, ['resolved', 'resolved_reports', 'closed', 'completed'], 0);
$todayCount     = $pickNum($stats, ['today', 'today_count', 'reports_today'], 0);

/* Fallback from current data set when stats empty */
if ($totalReports === 0 && !empty($reports)) {
    $totalReports   = count($reports);
    $pendingReports = 0;
    $progressCount  = 0;
    $resolvedCount  = 0;
    $todayCount     = 0;
    $today          = date('Y-m-d');

    foreach ($reports as $r) {
        $meta = $statusMeta((string) ($r['status'] ?? ''));
        if ($meta['key'] === 'pending')  $pendingReports++;
        if ($meta['key'] === 'progress') $progressCount++;
        if ($meta['key'] === 'resolved') $resolvedCount++;
        $when = (string) ($r['created_at'] ?? $r['created_on'] ?? '');
        if ($when !== '' && strpos($when, $today) === 0) $todayCount++;
    }
}

/* ---------- Flash ---------- */
$flashType = (string) ($flash['type'] ?? '');
$flashMsg  = (string) ($flash['message'] ?? '');

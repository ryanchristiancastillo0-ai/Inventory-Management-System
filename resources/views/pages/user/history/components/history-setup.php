<?php
/**
 * ORDER HISTORY SETUP
 * Variable defaults and helpers. Required first by ../index.php.
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

<?php
/**
 * ORDERS PAGE SETUP
 * Variable defaults, helpers and lookups. Required first by ../index.php.
 *
 * Expected variables (all optional — falls back to empty defaults):
 *   @var array      $orders
 *   @var array      $products
 *   @var array      $suppliers
 *   @var int        $totalOrders, $pendingOrders, $orderedOrders,
 *                   $receivedOrders, $cancelledOrders
 *   @var float      $totalRestockCost
 *   @var array|null $selectedOrder
 *   @var array      $orderItems
 *   @var string     $csrfToken
 */

$orders           = $orders           ?? [];
$products         = $products         ?? [];
$suppliers        = $suppliers        ?? [];
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

/* Real suppliers from the suppliers table (loaded by OrderController::index) */
$suppliers = $suppliers ?? [];

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

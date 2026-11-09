<?php
/**
 * STOCK MOVEMENTS SETUP
 * Variable defaults, stats derivation and helpers. Required first by ../index.php.
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

<?php
/**
 * CATALOG SETUP — shared variable preparation for Products / Inventory.
 * Required first by pages/user/products/index.php and pages/user/inventory/index.php.
 *
 * Expects these variables to be available. If not passed
 * explicitly, it falls back to the controllers in scope:
 *   @var array  $products    rows from ProductController::getAllProducts()
 *   @var array  $categories  rows from CategoryController::getAllCategories()
 *   @var string $csrfToken
 */

$products   = $products   ?? (isset($productController)   ? $productController->getAllProducts()     : []);
$categories = $categories ?? (isset($categoryController) ? $categoryController->getAllCategories() : []);
$csrfToken  = $csrfToken  ?? '';
$catalogTitle = $catalogTitle ?? 'Products';

// Category id → name lookup (tolerates id / category_id keys)
$catLookup = [];
foreach ($categories as $c) {
    $cid = (int) ($c['id'] ?? $c['category_id'] ?? 0);
    if ($cid) $catLookup[$cid] = (string) ($c['name'] ?? '');
}

// Compute stats
$totalProducts = count($products);
$inStock = $lowStock = $outOfStock = 0;
$totalValue = 0.0;
foreach ($products as $p) {
    $qty = (int) ($p['quantity'] ?? 0);
    $thr = (int) ($p['low_stock_threshold'] ?? 10);
    $prc = (float) ($p['price'] ?? 0);
    $totalValue += $prc * $qty;
    if ($qty <= 0)               $outOfStock++;
    elseif ($qty <= $thr)        $lowStock++;
    else                         $inStock++;
}

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

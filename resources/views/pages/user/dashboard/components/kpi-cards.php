<?php
/**
 * KPI CARDS COMPONENT
 * Displays key performance indicators for inventory management.
 *
 * @var array $stats - Array containing statistics like total products, categories, etc.
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="kpi-cards">
    <div class="card-grid">
        <div class="card-app kpi-card">
            <div class="kpi-value"><?= $e($totalProducts ?? 0) ?></div>
            <div class="kpi-label">Total Products</div>
        </div>
        <div class="card-app kpi-card">
            <div class="kpi-value"><?= $e($totalCategories ?? 0) ?></div>
            <div class="kpi-label">Categories</div>
        </div>
        <div class="card-app kpi-card">
            <div class="kpi-value"><?= $e($totalLowStock ?? 0) ?></div>
            <div class="kpi-label">Low Stock Items</div>
        </div>
        <div class="card-app kpi-card">
            <div class="kpi-value"><?= $e($totalStock ?? 0) ?></div>
            <div class="kpi-label">Total Stock</div>
        </div>
    </div>
</div>
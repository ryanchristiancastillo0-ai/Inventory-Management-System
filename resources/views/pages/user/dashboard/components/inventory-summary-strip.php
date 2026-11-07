<?php
/**
 * INVENTORY SUMMARY STRIP COMPONENT
 * Displays a summary of inventory status with visual indicators.
 *
 * @var array $stats - Array containing inventory statistics
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="inventory-summary">
    <div class="summary-strip">
        <div class="summary-item">
            <span class="summary-label">Total Value:</span>
            <span class="summary-value"><?= $e(number_format($totalInventoryValue ?? 0, 2)) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">In Stock:</span>
            <span class="summary-value"><?= $e($totalStockIn ?? 0) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Out of Stock:</span>
            <span class="summary-value"><?= $e($outOfStock ?? 0) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Movements:</span>
            <span class="summary-value"><?= $e($totalMovements ?? 0) ?></span>
        </div>
    </div>
</div>
<?php
/**
 * RECENT ACTIVITY TABLE COMPONENT
 * Rich replacement wired to the dashboard page's JS contract:
 *   #activityBody rows with data-type (all|in|out|alert),
 *   .segmented-item filter tabs, #noResults, #showingLabel,
 *   .product-cell imagery, .qty-* and .status-* pills.
 *
 * @var array $recentMovements - stock movement rows joined with products
 */
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="panel" aria-labelledby="recentActivityTitle">
    <div class="panel-head">
        <div>
            <h2 class="panel-title" id="recentActivityTitle" style="font-size:15px;font-weight:600;margin:0">Recent activity</h2>
            <p style="font-size:12px;color:var(--text-tertiary);margin:2px 0 0">Latest stock movements on your inventory</p>
        </div>
        <div class="segmented" role="tablist" aria-label="Filter movements">
            <button type="button" class="segmented-item active" data-filter="all" role="tab" aria-selected="true">All</button>
            <button type="button" class="segmented-item" data-filter="in" role="tab" aria-selected="false">Stock In</button>
            <button type="button" class="segmented-item" data-filter="out" role="tab" aria-selected="false">Stock Out</button>
            <button type="button" class="segmented-item" data-filter="alert" role="tab" aria-selected="false">Alerts</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="activity-table align-middle">
            <caption>Recent inventory activity: product, SKU, category, activity, quantity, date and status.</caption>
            <thead>
                <tr>
                    <th scope="col">Product</th>
                    <th scope="col">SKU</th>
                    <th scope="col">Category</th>
                    <th scope="col">Activity</th>
                    <th scope="col">Quantity</th>
                    <th scope="col">Date</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody id="activityBody">
                <?php foreach ($recentMovements ?? [] as $m):
                    $name      = (string) ($m['product_name'] ?? '');
                    $initial   = mb_strtoupper(mb_substr($name !== '' ? $name : '?', 0, 1));
                    $type      = ($m['movement_type'] ?? '') === 'OUT' ? 'out' : 'in';
                    $qty       = (int) ($m['quantity'] ?? 0);
                    $pQty      = $m['product_quantity'] ?? null;
                    $thr       = (int) ($m['product_threshold'] ?? 10);
                    $skipBadge = false;

                    // Alert = stock-out movement that left the product at/below threshold
                    if ($type === 'out' && $pQty !== null && (int) $pQty <= $thr) {
                        $rowType = 'alert';
                    } else {
                        $rowType = $type;
                    }

                    $when = (string) ($m['created_at'] ?? '');
                    $whenTs = strtotime($when);
                    $whenText = $whenTs ? date('M j, Y · g:ia', $whenTs) : $when;
                ?>
                    <tr data-type="<?= $e($rowType) ?>">
                        <td>
                            <div class="product-cell">
                                <?php $pImage = (string) ($m['product_image'] ?? ''); ?>
                                <?php if ($pImage !== ''): ?>
                                    <img class="product-avatar product-avatar--img" src="<?= $e($pImage) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="product-avatar" aria-hidden="true"><?= $e($initial) ?></span>
                                <?php endif; ?>
                                <span class="product-name"><?= $e($name) ?></span>
                            </div>
                        </td>
                        <td class="cell-sku"><?= $e($m['sku'] ?? '') ?></td>
                        <td><?= $e($m['category_name'] ?? '') ?></td>
                        <td><?= $e($type === 'in' ? 'Stock In' : 'Stock Out') ?></td>
                        <td class="qty <?= $e($type === 'in' ? 'qty-in' : 'qty-out') ?>"><?= $e($qty) ?></td>
                        <td class="cell-when"><?= $e($whenText) ?></td>
                        <td>
                            <?php
                            $pQtyInt = $pQty === null ? null : (int) $pQty;
                            if ($type === 'in'):
                                $statusLabel = 'In stock';   $statusCls = 'status-success';
                            elseif ($pQtyInt !== null && $pQtyInt <= 0):
                                $statusLabel = 'Out of stock'; $statusCls = 'status-danger';
                            elseif ($pQtyInt !== null && $pQtyInt <= $thr):
                                $statusLabel = 'Low stock';    $statusCls = 'status-warning';
                            else:
                                $statusLabel = 'Stock out';    $statusCls = '';
                            endif;
                            ?>
                            <span class="status <?= $e($statusCls) ?>"><?= $e($statusLabel) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="no-results" id="noResults">No movements match your filter.</div>
    </div>
    <div style="padding:0 20px 20px">
        <p class="showing-count" id="showingLabel">
            Showing <?= count($recentMovements ?? []) ?> of <?= count($recentMovements ?? []) ?> movements
        </p>
    </div>
</section>

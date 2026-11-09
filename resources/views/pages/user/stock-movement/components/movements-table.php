<!-- ==========================================================
     COMPONENT: Movements table
     ========================================================== -->
<section class="section-gap">
    <div class="panel">

        <?php if (empty($movements)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></div>
                <h3 class="empty-title">No stock movements yet</h3>
                <p class="empty-text">Movements will appear here as soon as stock is received, sold, or adjusted.</p>
                <button type="button" class="btn-primary-app" onclick="var b = document.getElementById('addMovementBtn'); if (b) b.click();">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>New movement
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table activity-table align-middle">
                    <caption>Stock movements: product, type, quantity, stock flow, remarks and date.</caption>
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Type</th>
                            <th scope="col" class="text-end">Qty</th>
                            <th scope="col">Stock flow</th>
                            <th scope="col">Remarks</th>
                            <th scope="col">When</th>
                            <th scope="col" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody id="movementsBody">
                        <?php foreach ($movements as $m):
                            $mid      = (int) ($m['id'] ?? 0);
                            $pid      = (int) ($m['product_id'] ?? 0);
                            $pname    = (string) ($m['product_name'] ?? ($productOptions[$pid]['name'] ?? ('Product #' . $pid)));
                            $psku     = (string) ($m['sku'] ?? ($productOptions[$pid]['sku'] ?? ''));
                            $typeRaw  = (string) ($m['movement_type'] ?? '');
                            $qty      = (int) ($m['quantity'] ?? 0);
                            $before   = isset($m['previous_stock']) ? (int) $m['previous_stock'] : (isset($m['stock_before']) ? (int) $m['stock_before'] : null);
                            $after    = isset($m['new_stock'])      ? (int) $m['new_stock']      : (isset($m['stock_after'])  ? (int) $m['stock_after']  : null);
                            $remarks  = (string) ($m['remarks'] ?? '');
                            $when     = $pick($m, ['created_at', 'created_on', 'date', 'created'], '');

                            $meta     = $typeMeta($typeRaw);
                            $sign     = $meta['sign'];
                            $isPos    = ($sign === '+');
                            $isNeg    = ($sign === '−');
                            $qtyCls   = $isPos ? 'is-pos' : ($isNeg ? 'is-neg' : 'is-zero');
                            $qtyLabel = $sign . number_format(abs($qty));
                        ?>
                            <tr class="movement-row"
                                data-movement-id="<?= $mid ?>"
                                data-movement-type="<?= $e(strtoupper(trim($typeRaw))) ?>"
                                data-product-name="<?= $e($pname) ?>"
                                data-product-sku="<?= $e($psku) ?>"
                                data-remarks="<?= $e($remarks) ?>"
                                onclick="if (!event.target.closest('a,button,.dropdown-menu')) { window.location.href = '/stock-movements?id=<?= $mid ?>'; }"
                            >
                                <td class="cell-product">
                                    <div class="name"><?= $e($pname) ?></div>
                                    <?php if ($psku !== ''): ?>
                                        <div class="sku"><?= $e($psku) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="type-badge <?= $e($meta['cls']) ?>">
                                        <i class="bi <?= $e($meta['icon']) ?>" aria-hidden="true"></i>
                                        <?= $e($meta['label']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="qty-delta <?= $qtyCls ?>"><?= $e($qtyLabel) ?></span>
                                </td>
                                <td>
                                    <?php if ($before !== null || $after !== null): ?>
                                        <span class="flow">
                                            <span class="from"><?= $before !== null ? number_format($before) : '—' ?></span>
                                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                            <span class="to"><?= $after !== null ? number_format($after) : '—' ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="cell-when">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-remarks" title="<?= $e($remarks) ?>">
                                    <?= $remarks !== '' ? $e($remarks) : '—' ?>
                                </td>
                                <td class="cell-when"><?= $e($fmtWhen($when)) ?></td>
                                <td class="cell-actions">
                                    <!-- Row actions — Bootstrap dropdown -->
                                    <div class="dropdown">
                                        <button type="button" class="icon-btn row-actions-btn" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Row actions">
                                            <i class="bi bi-three-dots" aria-hidden="true"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="/stock-movements?id=<?= $mid ?>">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>View details
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" data-row-action="copy" data-movement-id="<?= $mid ?>">
                                                    <i class="bi bi-clipboard" aria-hidden="true"></i>Copy movement ID
                                                </button>
                                            </li>
                                            <?php if ($pid > 0): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="/products?id=<?= $pid ?>">
                                                        <i class="bi bi-box" aria-hidden="true"></i>Open product
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="/stock-movements?product_id=<?= $pid ?>">
                                                        <i class="bi bi-list-ul" aria-hidden="true"></i>All movements for product
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="no-results" id="noResults" role="status">No movements match your filters.</div>

            <div class="table-footer">
                <span id="showingLabel">
                    Showing 1–<?= number_format($totalMovements) ?> of <?= number_format($totalMovements) ?>
                </span>
                <div class="pagination" id="pagination" aria-label="Pagination"></div>
            </div>
        <?php endif; ?>

    </div>
</section>

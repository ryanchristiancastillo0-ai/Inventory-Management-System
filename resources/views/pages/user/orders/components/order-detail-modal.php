<?php if ($selectedOrder !== null):
    $soid     = (int) ($selectedOrder['id'] ?? 0);
    $sonum    = (string) ($selectedOrder['order_number'] ?? $selectedOrder['number'] ?? ('#' . $soid));
    $sosid    = (int) ($selectedOrder['supplier_id'] ?? 0);
    $sosname  = (string) ($selectedOrder['supplier_name'] ?? '');
    $sostatus = strtoupper((string) ($selectedOrder['status'] ?? 'PENDING'));
    $sototal  = (float) ($selectedOrder['total_amount'] ?? 0);
    $sowhen   = $pick($selectedOrder, ['created_at', 'created_on', 'date', 'created'], '');

    $soMeta       = $statusMeta($sostatus);
    $soStatusLabel = $soMeta[0];
    $soStatusCls   = $soMeta[1];

    $isPending    = $sostatus === 'PENDING';
    $isOrdered    = $sostatus === 'ORDERED';
    $canCancel    = $isPending || $isOrdered;
    $canSubmit    = $isPending && !empty($orderItems);
    $canEditItems = $isPending;

    $statusIconMap = [
        'PENDING'   => 'bi-hourglass-split',
        'ORDERED'   => 'bi-send',
        'RECEIVED'  => 'bi-check2-circle',
        'CANCELLED' => 'bi-x-circle',
    ];
    $statusIcon = $statusIconMap[$sostatus] ?? 'bi-receipt';
?>
<!-- ==========================================================
     COMPONENT: Order detail modal (auto-opens on ?id=X)
     ========================================================== -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-app">

            <div class="modal-header-app">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <span class="order-modal-icon <?= $soStatusCls ?>">
                        <i class="bi <?= $statusIcon ?>" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="modal-title-app" id="orderModalTitle"><?= $e($sonum) ?></h2>
                        <p class="modal-subtitle-app"><?= $e($soStatusLabel) ?> order</p>
                    </div>
                </div>
                <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-body-app">
                <div class="meta-grid">
                    <div class="meta-item">
                        <p class="meta-label">Supplier</p>
                        <p class="meta-value is-muted">
                            <?= $sosname !== '' ? $e($sosname) : 'Supplier #' . $sosid ?>
                        </p>
                    </div>
                    <div class="meta-item">
                        <p class="meta-label">Created</p>
                        <p class="meta-value is-muted"><?= $e($fmtWhen($sowhen)) ?></p>
                    </div>
                    <div class="meta-item">
                        <p class="meta-label">Status</p>
                        <p class="meta-value"><span class="status <?= $soStatusCls ?>"><?= $e($soStatusLabel) ?></span></p>
                    </div>
                    <div class="meta-item">
                        <p class="meta-label">Total</p>
                        <p class="meta-value is-mono">₱<?= number_format($sototal, 2) ?></p>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between" style="margin-top:26px;margin-bottom:8px">
                    <p class="meta-label" style="margin:0">Items</p>
                    <span class="filter-count"><?= count($orderItems) ?> item<?= count($orderItems) === 1 ? '' : 's' ?></span>
                </div>

                <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col" class="num" style="width:90px">Qty</th>
                                <th scope="col" class="num" style="width:110px">Unit price</th>
                                <th scope="col" class="num" style="width:110px">Subtotal</th>
                                <th scope="col" class="num" style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orderItems)): ?>
                                <tr><td colspan="5"><div class="items-empty">No items yet<?= $isPending ? ' — add one below.' : '.' ?></div></td></tr>
                            <?php else: ?>
                                <?php foreach ($orderItems as $it):
                                    $iid = (int) ($it['id'] ?? 0);
                                    $pid = (int) ($it['product_id'] ?? 0);
                                    $pname = (string) ($it['product_name'] ?? ($productOptions[$pid]['name'] ?? ('Product #' . $pid)));
                                    $psku  = (string) ($it['sku'] ?? ($productOptions[$pid]['sku'] ?? ''));
                                    $qty   = (int) ($it['quantity'] ?? 0);
                                    $price = (float) ($it['unit_price'] ?? 0);
                                    $sub   = $qty * $price;
                                ?>
                                    <tr>
                                        <td>
                                            <div class="item-name"><?= $e($pname) ?></div>
                                            <?php if ($psku !== ''): ?>
                                                <div class="item-sku"><?= $e($psku) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="num">
                                            <?php if ($canEditItems): ?>
                                                <form id="updateForm-<?= $iid ?>" method="post" action="/orders/items/update" style="display:none">
                                                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                                    <input type="hidden" name="item_id" value="<?= $iid ?>">
                                                </form>
                                                <input class="inline-input" type="number" name="quantity" min="1" value="<?= $qty ?>" form="updateForm-<?= $iid ?>">
                                            <?php else: ?>
                                                <?= number_format($qty) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="num">
                                            <?php if ($canEditItems): ?>
                                                <input class="inline-input is-wide" type="number" name="unit_price" min="0" step="0.01" value="<?= number_format($price, 2, '.', '') ?>" form="updateForm-<?= $iid ?>">
                                            <?php else: ?>
                                                ₱<?= number_format($price, 2) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="num item-subtotal">₱<?= number_format($sub, 2) ?></td>
                                        <td class="num">
                                            <?php if ($canEditItems): ?>
                                                <button type="submit" form="updateForm-<?= $iid ?>" class="btn-icon-sm" title="Update item">
                                                    <i class="bi bi-check2" aria-hidden="true"></i>
                                                </button>
                                                <form id="deleteForm-<?= $iid ?>" method="post" action="/orders/items/delete" style="display:inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                                    <input type="hidden" name="item_id" value="<?= $iid ?>">
                                                    <button type="submit" class="btn-icon-sm is-danger" title="Remove item" onclick="return confirm('Remove this item from the order?')">
                                                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ($canEditItems): ?>
                                <tr class="add-row">
                                    <td colspan="5">
                                        <form class="add-item-form" method="post" action="/orders/items/add">
                                            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                            <input type="hidden" name="order_id" value="<?= $soid ?>">

                                            <select name="product_id" required>
                                                <option value="">Add a product…</option>
                                                <?php foreach ($productOptions as $pid => $pinfo): ?>
                                                    <option value="<?= (int) $pid ?>"><?= $e($pinfo['name']) ?><?= $pinfo['sku'] !== '' ? ' · ' . $e($pinfo['sku']) : '' ?></option>
                                                <?php endforeach; ?>
                                            </select>

                                            <input type="number" name="quantity" min="1" required placeholder="Qty" aria-label="Quantity">
                                            <input type="number" name="unit_price" min="0" step="0.01" required placeholder="Unit price" aria-label="Unit price">

                                            <button type="submit" class="btn-secondary" style="height:34px">
                                                <i class="bi bi-plus-lg" aria-hidden="true"></i>Add
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer-app">
                <?php if ($canCancel): ?>
                    <form method="post" action="/orders/cancel" style="margin-right:auto">
                        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                        <input type="hidden" name="order_id" value="<?= $soid ?>">
                        <button type="submit" class="btn-secondary" style="color:var(--danger);border-color:var(--border)" onclick="return confirm('Cancel this order?')">
                            <i class="bi bi-x-circle" aria-hidden="true"></i>Cancel order
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($isPending): ?>
                    <form method="post" action="/orders/submit" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                        <input type="hidden" name="order_id" value="<?= $soid ?>">
                        <button type="submit" class="btn-primary-app" <?= !$canSubmit ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>>
                            <i class="bi bi-send" aria-hidden="true"></i>Place order
                        </button>
                    </form>
                <?php endif; ?>

                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
<?php endif; ?>

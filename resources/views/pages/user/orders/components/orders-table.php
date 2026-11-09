<!-- ==========================================================
     COMPONENT: Orders table
     ========================================================== -->
<section class="section-gap">
    <div class="panel">

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-truck" aria-hidden="true"></i></div>
                <h3 class="empty-title">No restock orders yet</h3>
                <p class="empty-text">Create your first restock order to request products from a supplier.</p>
                <button type="button" class="btn-primary-app" onclick="var b = document.getElementById('addOrderBtn'); if (b) b.click();">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>New order
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table activity-table align-middle">
                    <caption>Restock orders: order number, supplier, status, items, total and creation date.</caption>
                    <thead>
                        <tr>
                            <th scope="col">Order</th>
                            <th scope="col">Supplier</th>
                            <th scope="col">Status</th>
                            <th scope="col">Items</th>
                            <th scope="col">Total</th>
                            <th scope="col">Created</th>
                            <th scope="col" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody id="ordersBody">
                        <?php foreach ($orders as $o):
                            $oid    = (int) ($o['id'] ?? 0);
                            $num    = (string) ($o['order_number'] ?? $o['number'] ?? ('#' . $oid));
                            $sid    = (int) ($o['supplier_id'] ?? 0);
                            $sname  = (string) ($o['supplier_name'] ?? '');
                            $status = strtoupper((string) ($o['status'] ?? 'PENDING'));
                            $total  = (float) ($o['total_amount'] ?? 0);
                            $items  = (int) ($o['items_count'] ?? $o['item_count'] ?? 0);
                            $when   = $pick($o, ['created_at', 'created_on', 'date', 'created'], '');

                            $meta = $statusMeta($status);
                            $statusLabel = $meta[0];
                            $statusCls   = $meta[1];
                        ?>
                            <tr class="order-row"
                                data-order-id="<?= $oid ?>"
                                data-order-number="<?= $e($num) ?>"
                                data-status="<?= $e($status) ?>"
                                data-supplier-name="<?= $e($sname) ?>"
                                onclick="if (!event.target.closest('a,button,.dropdown-menu-app')) { window.location.href = '/orders?id=<?= $oid ?>'; }"
                            >
                                <td>
                                    <a class="order-ref" href="/orders?id=<?= $oid ?>">
                                        <i class="bi bi-receipt" aria-hidden="true"></i>
                                        <?= $e($num) ?>
                                    </a>
                                </td>
                                <td class="cell-muted">
                                    <?= $sname !== '' ? $e($sname) : 'Supplier #' . $sid ?>
                                </td>
                                <td><span class="status <?= $statusCls ?>"><?= $e($statusLabel) ?></span></td>
                                <td class="cell-items"><?= $items > 0 ? number_format($items) : '—' ?></td>
                                <td class="cell-total">₱<?= number_format($total, 2) ?></td>
                                <td class="cell-when"><?= $e($fmtWhen($when)) ?></td>
                                <td class="cell-actions">
                                    <div class="dropdown-app">
                                        <button type="button" class="icon-btn row-actions-btn" data-dropdown-toggle="orderMenu-<?= $oid ?>" aria-label="Row actions" aria-haspopup="menu" aria-expanded="false">
                                            <i class="bi bi-three-dots" aria-hidden="true"></i>
                                        </button>
                                        <div class="dropdown-menu-app" id="orderMenu-<?= $oid ?>" role="menu">
                                            <button type="button" class="dropdown-item-app" data-row-action="view"><i class="bi bi-eye" aria-hidden="true"></i>View / manage</button>
                                            <button type="button" class="dropdown-item-app" data-row-action="copy"><i class="bi bi-clipboard" aria-hidden="true"></i>Copy order #</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="no-results" id="noResults" role="status">No orders match your filters.</div>

            <div class="table-footer">
                <span id="showingLabel">
                    Showing 1–<?= number_format($totalOrders) ?> of <?= number_format($totalOrders) ?>
                </span>
                <div class="pagination" id="pagination" aria-label="Pagination"></div>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- ==========================================================
     COMPONENT: History table
     ========================================================== -->
<section class="section-gap">
    <div class="panel">

        <?php if (empty($history)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></div>
                <h3 class="empty-title">No history records<?= $hasServerFilter ? ' matching your filters' : ' yet' ?></h3>
                <p class="empty-text">
                    <?= $hasServerFilter
                        ? 'Try clearing the filters to see all records.'
                        : 'Order activity will appear here once orders are created, updated, completed or cancelled.' ?>
                </p>
                <?php if ($hasServerFilter): ?>
                    <a href="/order-history" class="btn-secondary">
                        <i class="bi bi-x" aria-hidden="true"></i>Clear filters
                    </a>
                <?php else: ?>
                    <a href="/orders" class="btn-primary-app">
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>Go to orders
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table activity-table align-middle">
                    <caption>Order history: action, order reference, details, performer and timestamp.</caption>
                    <thead>
                        <tr>
                            <th scope="col">When</th>
                            <th scope="col">Action</th>
                            <th scope="col">Order</th>
                            <th scope="col">Details</th>
                            <th scope="col">By</th>
                            <th scope="col" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody id="historyBody">
                        <?php foreach ($history as $rec):
                            $hid        = (int) ($rec['id'] ?? 0);
                            $orderId    = (int) ($rec['order_id'] ?? 0);
                            $orderNum   = $pick($rec, ['order_number', 'number', 'order_no'], $orderId > 0 ? ('#' . $orderId) : '—');
                            $actionRaw  = (string) ($rec['action'] ?? '');
                            $details    = $pick($rec, ['description', 'details', 'notes', 'remarks', 'message', 'summary'], '');
                            $performedBy = $pick($rec, ['performed_by', 'user_name', 'created_by', 'user', 'actor'], '');
                            $oldStatus  = $pick($rec, ['old_status', 'previous_status', 'from_status'], '');
                            $newStatus  = $pick($rec, ['new_status', 'to_status', 'status'], '');
                            $when       = $pick($rec, ['created_at', 'created_on', 'date', 'created', 'timestamp'], '');

                            $meta = $actionMeta($actionRaw);
                            $performerInitial = $performedBy !== '' ? strtoupper(substr($performedBy, 0, 1)) : '?';
                        ?>
                            <tr class="history-row"
                                data-history-id="<?= $hid ?>"
                                data-action="<?= $e(strtoupper($actionRaw)) ?>"
                                data-order-number="<?= $e($orderNum) ?>"
                                data-order-id="<?= $orderId ?>"
                                data-performed-by="<?= $e($performedBy) ?>"
                                data-details="<?= $e($details) ?>"
                                onclick="if (!event.target.closest('a,button,.dropdown-menu')) { window.location.href = '/order-history/view?id=<?= $hid ?>'; }"
                            >
                                <td class="cell-when"><?= $e($fmtWhen($when)) ?></td>
                                <td>
                                    <span class="action-badge <?= $e($meta['cls']) ?>">
                                        <i class="bi <?= $e($meta['icon']) ?>" aria-hidden="true"></i>
                                        <?= $e($meta['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($orderId > 0): ?>
                                        <a class="order-ref" href="/orders?id=<?= $orderId ?>">
                                            <i class="bi bi-receipt" aria-hidden="true"></i>
                                            <?= $e($orderNum) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="cell-when">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-details" title="<?= $e($details) ?>">
                                    <?php if ($details !== ''): ?>
                                        <?= $e($details) ?>
                                    <?php elseif ($oldStatus !== '' || $newStatus !== ''): ?>
                                        <span class="status-flow">
                                            <span class="status <?= $e($statusCls($oldStatus)) ?>"><?= $e($oldStatus !== '' ? $oldStatus : '—') ?></span>
                                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                            <span class="status <?= $e($statusCls($newStatus)) ?>"><?= $e($newStatus !== '' ? $newStatus : '—') ?></span>
                                        </span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($performedBy !== ''): ?>
                                        <span class="cell-by">
                                            <span class="mini-avatar" aria-hidden="true"><?= $e($performerInitial) ?></span>
                                            <?= $e($performedBy) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="cell-when">System</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-actions">
                                    <!-- Row actions — Bootstrap dropdown -->
                                    <div class="dropdown">
                                        <button type="button" class="icon-btn row-actions-btn" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Row actions">
                                            <i class="bi bi-three-dots" aria-hidden="true"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="/order-history/view?id=<?= $hid ?>">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>View details
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" data-row-action="copy" data-order-number="<?= $e($orderNum) ?>">
                                                    <i class="bi bi-clipboard" aria-hidden="true"></i>Copy order #
                                                </button>
                                            </li>
                                            <?php if ($orderId > 0): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="/orders?id=<?= $orderId ?>">
                                                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Open order
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="/order-history?order_id=<?= $orderId ?>">
                                                        <i class="bi bi-list-ul" aria-hidden="true"></i>All history for order
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

            <div class="no-results" id="noResults" role="status">No records match your search.</div>

            <div class="table-footer">
                <span id="showingLabel">
                    Showing 1–<?= number_format(count($history)) ?> of <?= number_format(count($history)) ?>
                </span>
                <div class="pagination" id="pagination" aria-label="Pagination"></div>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- ==========================================================
     COMPONENT: Products table (bulk bar, empty state, table, footer)
     ========================================================== -->
<section class="section-gap">
    <div class="panel">

        <!-- Bulk action bar (shown when rows are selected) -->
        <div class="bulk-bar" id="bulkBar" hidden>
            <span class="bulk-bar__label"><span id="bulkCount">0</span> selected</span>
            <div class="bulk-bar__actions">
                <button type="button" class="btn-text" id="bulkExportBtn"><i class="bi bi-download" aria-hidden="true"></i>Export</button>
                <button type="button" class="btn-text is-danger" id="bulkDeleteBtn"><i class="bi bi-trash" aria-hidden="true"></i>Delete</button>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                <h3 class="empty-title">No products yet</h3>
                <p class="empty-text">Add your first product to start tracking inventory.</p>
                <button type="button" class="btn-primary-app" onclick="document.getElementById('addProductBtn').click()">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>Add first product
                </button>
            </div>
        <?php endif; ?>
            <div class="table-responsive" <?= empty($products) ? 'hidden' : '' ?>>
                <table class="table activity-table align-middle">
                    <caption>Product catalog with name, SKU, category, price, stock and status.</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="th-check">
                                <input type="checkbox" class="checkbox-app" id="selectAll" aria-label="Select all products">
                            </th>
                            <th scope="col">Product</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Category</th>
                            <th scope="col" class="th-price">Price</th>
                            <th scope="col" class="th-stock">Stock</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="th-actions"></th>
                        </tr>
                    </thead>
                    <tbody id="productBody">
                        <?php foreach ($products as $p):
                            $pid   = (int) ($p['id'] ?? 0);
                            $name  = (string) ($p['name'] ?? '');
                            $sku   = (string) ($p['sku'] ?? '');
                            $desc  = (string) ($p['description'] ?? '');
                            $image = (string) ($p['image_path'] ?? '');
                            $catId = (int) ($p['category_id'] ?? 0);
                            $catNm = $catLookup[$catId] ?? 'Uncategorized';
                            $price = (float) ($p['price'] ?? 0);
                            $qty   = (int) ($p['quantity'] ?? 0);
                            $thr   = (int) ($p['low_stock_threshold'] ?? 10);

                            if ($qty <= 0) {
                                $statusKey = 'out'; $statusLabel = 'Out of stock';
                                $statusCls = 'status-danger';
                            } elseif ($qty <= $thr) {
                                $statusKey = 'low'; $statusLabel = 'Low stock';
                                $statusCls = 'status-warning';
                            } else {
                                $statusKey = 'in'; $statusLabel = 'In stock';
                                $statusCls = 'status-success';
                            }

                            $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '?';

                            // Stock bar — scale to 3× the threshold so in-stock items read full
                            $maxBar = max($thr * 3, 30);
                            $barWidth = $maxBar > 0 ? min(100, ($qty / $maxBar) * 100) : 0;
                            $barCls = $statusKey === 'out' ? ' is-out' : ($statusKey === 'low' ? ' is-low' : '');
                        ?>
                            <tr
                                data-id="<?= $pid ?>"
                                data-name="<?= $e($name) ?>"
                                data-sku="<?= $e($sku) ?>"
                                 data-category="<?= $catId ?>"
                                 data-category-name="<?= $e($catNm) ?>"
                                data-status="<?= $statusKey ?>"
                                data-price="<?= $e($price) ?>"
                                data-quantity="<?= $qty ?>"
                                data-threshold="<?= $thr ?>"
                                data-description="<?= $e($desc) ?>"
                                data-image="<?= $e($image) ?>"
                            >
                                <td class="td-check">
                                    <input type="checkbox" class="checkbox-app row-check" aria-label="Select <?= $e($name) ?>">
                                </td>
                                <td>
                                    <div class="product-cell">
                                        <?php if ($image !== ''): ?>
                                            <img class="product-avatar product-avatar--img" src="<?= $e($image) ?>" alt="" loading="lazy">
                                        <?php else: ?>
                                            <span class="product-avatar" aria-hidden="true"><?= $e($initial) ?></span>
                                        <?php endif; ?>
                                        <div class="product-cell-body">
                                            <div class="product-name"><?= $e($name) ?></div>
                                            <?php if ($desc !== ''): ?>
                                                <div class="product-meta"><?= $e(mb_strimwidth($desc, 0, 48, '…')) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="cell-sku"><?= $e($sku) ?></td>
                                <td><span class="chip"><?= $e($catNm) ?></span></td>
                                <td class="cell-price text-end">₱<?= number_format($price, 2) ?></td>
                                <td class="text-end">
                                    <div class="stock-cell">
                                        <span class="stock-num">
                                            <?= number_format($qty) ?><small>units</small>
                                        </span>
                                        <div class="stock-bar">
                                            <div class="stock-bar-fill<?= $barCls ?>" style="width: <?= round($barWidth) ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="status <?= $statusCls ?>"><?= $e($statusLabel) ?></span></td>
                                <td class="td-actions">
                                    <div class="dropdown-app">
                                        <button type="button" class="icon-btn row-actions-btn" data-dropdown-toggle="rowMenu-<?= $pid ?>" aria-label="Row actions" aria-haspopup="menu" aria-expanded="false">
                                            <i class="bi bi-three-dots" aria-hidden="true"></i>
                                        </button>
                                        <div class="dropdown-menu-app" id="rowMenu-<?= $pid ?>" role="menu">
                                             <button type="button" class="dropdown-item-app" data-row-action="edit"><i class="bi bi-pencil" aria-hidden="true"></i>Edit</button>
                                             <button type="button" class="dropdown-item-app" data-row-action="duplicate"><i class="bi bi-files" aria-hidden="true"></i>Duplicate</button>
                                            <div class="dropdown-divider-app"></div>
                                            <button type="button" class="dropdown-item-app is-danger" data-row-action="delete"><i class="bi bi-trash" aria-hidden="true"></i>Delete</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="no-results" id="noResults" role="status">No products match your filters.</div>

            <div class="table-footer" <?= empty($products) ? 'hidden' : '' ?>>
                <span id="showingLabel">
                    Showing 1–<?= number_format($totalProducts) ?> of <?= number_format($totalProducts) ?> products
                </span>
            </div>

    </div>
</section>

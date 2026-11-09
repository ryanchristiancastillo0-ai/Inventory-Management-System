<!-- ==========================================================
     COMPONENT: New movement modal
     ========================================================== -->
<div class="modal fade" id="newMovementModal" tabindex="-1" aria-labelledby="newMovementTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <form method="post" action="/stock-movements/create">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="newMovementTitle">Record stock movement</h2>
                        <p class="modal-subtitle-app">Log an in, out, adjustment, return or damage entry.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldProduct">Product</label>
                            <select class="form-select-app" id="fieldProduct" name="product_id" required>
                                <option value="">Select a product…</option>
                                <?php foreach ($productOptions as $pid => $pinfo): ?>
                                    <option value="<?= (int) $pid ?>">
                                        <?= $e($pinfo['name']) ?><?= $pinfo['sku'] !== '' ? ' · ' . $e($pinfo['sku']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($productOptions)): ?>
                                <p style="font-size:11px;color:var(--text-tertiary);margin:6px 0 0">
                                    No products available. Add a product first.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldType">Movement type</label>
                            <select class="form-select-app" id="fieldType" name="movement_type" required>
                                <option value="">Select a type…</option>
                                <option value="IN">Stock In</option>
                                <option value="OUT">Stock Out</option>
                                <option value="ADJUSTMENT">Adjustment</option>
                                <option value="RETURN">Return</option>
                                <option value="DAMAGE">Damage</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldQty">Quantity</label>
                            <input class="form-control-app" type="number" id="fieldQty" name="quantity" min="1" step="1" required placeholder="e.g. 25">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldRemarks">Remarks</label>
                            <textarea class="form-control-app" id="fieldRemarks" name="remarks" rows="3" maxlength="500" placeholder="Optional note — e.g. received from supplier, sold at POS, damaged during handling…"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app" <?= empty($productOptions) ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>>
                        <i class="bi bi-check2" aria-hidden="true"></i>Save movement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

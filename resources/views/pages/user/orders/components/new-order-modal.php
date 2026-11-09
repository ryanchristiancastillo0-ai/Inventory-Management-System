<!-- ==========================================================
     COMPONENT: New order modal
     ========================================================== -->
<div class="modal fade" id="newOrderModal" tabindex="-1" aria-labelledby="newOrderTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <form method="post" action="/orders/create">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="newOrderTitle">New restock order</h2>
                        <p class="modal-subtitle-app">Create a pending order and pick items next.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldSupplier">Supplier</label>
                            <?php if (!empty($suppliers)): ?>
                                <select class="form-select-app" id="fieldSupplier" name="supplier_id" required>
                                    <option value="">Select a supplier…</option>
                                    <?php foreach ($suppliers as $s): ?>
                                        <option value="<?= (int) $s['id'] ?>"><?= $e($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <select class="form-select-app" id="fieldSupplier" name="supplier_id" required disabled>
                                    <option value="">No suppliers yet</option>
                                </select>
                                <div class="supplier-empty-callout">
                                    <p class="supplier-empty-text">
                                        Restock orders need a supplier. Add your first one to get started.
                                    </p>
                                    <button type="button" class="btn-secondary btn-sm-app" data-suppliers-open data-return-to="new-order">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>Add a supplier
                                    </button>
                                </div>
                            <?php endif; ?>
                            <p style="font-size:11px;color:var(--text-tertiary);margin:6px 0 0">
                                Suppliers are managed from the Suppliers list on this page.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>Create order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

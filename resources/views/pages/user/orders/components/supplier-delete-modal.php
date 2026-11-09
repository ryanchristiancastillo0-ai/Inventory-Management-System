<!-- ==========================================================
     COMPONENT: Delete supplier modal (fields populated by suppliers.js)
     Blocked server-side while orders reference the supplier.
     ========================================================== -->
<div class="modal fade" id="supplierDeleteModal" tabindex="-1" aria-labelledby="supplierDeleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-content-app">
            <form method="post" action="/suppliers/delete">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="supplier_id" id="supplier-delete-id">
                <input type="hidden" name="return_to" value="suppliers">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="supplierDeleteTitle">Delete supplier</h2>
                        <p class="modal-subtitle-app">This action cannot be undone.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <p style="margin:0;font-size:13px;color:var(--text)">
                        Permanently delete
                        <strong id="supplier-delete-name">this supplier</strong>?
                    </p>
                    <p style="margin:8px 0 0;font-size:12px;color:var(--text-tertiary)">
                        Suppliers that already have restock orders cannot be deleted.
                    </p>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app" style="background:var(--danger)">
                        <i class="bi bi-trash" aria-hidden="true"></i>Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================
     COMPONENT: Delete confirmation modal
     ========================================================== -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <div class="modal-header-app">
                <div>
                    <h2 class="modal-title-app" id="deleteTitle">Delete product?</h2>
                    <p class="modal-subtitle-app">This action cannot be undone.</p>
                </div>
                <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body-app">
                <p style="margin:0;color:var(--text-secondary);font-size:14px">
                    Deleting <strong id="deleteProductName" style="color:var(--text)">this product</strong> will remove it from your inventory permanently.
                </p>
                <p id="deleteError" role="alert" style="color:var(--danger)" hidden></p>
            </div>
            <div class="modal-footer-app">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-primary-app" id="confirmDeleteBtn" style="background:var(--danger)">
                    <i class="bi bi-trash" aria-hidden="true"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================
     COMPONENT: Edit supplier modal (fields populated by suppliers.js)
     ========================================================== -->
<div class="modal fade" id="supplierEditModal" tabindex="-1" aria-labelledby="supplierEditTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <form method="post" action="/suppliers/update" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="supplier_id" id="supplier-edit-id">
                <input type="hidden" name="return_to" value="suppliers">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="supplierEditTitle">Edit supplier</h2>
                        <p class="modal-subtitle-app">Update the supplier's details.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="supplier-edit-name">Name <span class="req">*</span></label>
                            <input class="form-control-app" type="text" id="supplier-edit-name" name="name"
                                   required maxlength="150">
                        </div>
                    </div>
                    <div class="form-row two-col">
                        <div>
                            <label class="form-label-app" for="supplier-edit-contact">Contact person</label>
                            <input class="form-control-app" type="text" id="supplier-edit-contact" name="contact_person"
                                   maxlength="150">
                        </div>
                        <div>
                            <label class="form-label-app" for="supplier-edit-phone">Phone</label>
                            <input class="form-control-app" type="text" id="supplier-edit-phone" name="phone"
                                   maxlength="30">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="supplier-edit-email">Email</label>
                            <input class="form-control-app" type="email" id="supplier-edit-email" name="email"
                                   maxlength="255">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="supplier-edit-address">Address</label>
                            <input class="form-control-app" type="text" id="supplier-edit-address" name="address"
                                   maxlength="500">
                        </div>
                    </div>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app">
                        <i class="bi bi-check2" aria-hidden="true"></i>Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

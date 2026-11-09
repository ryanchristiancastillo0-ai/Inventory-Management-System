<!-- ==========================================================
     COMPONENT: Suppliers manage modal (list + add form)
     Also the entry point for supplier edit / delete.
     ========================================================== -->
<div class="modal fade" id="suppliersModal" tabindex="-1" aria-labelledby="suppliersTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-app">

            <div class="modal-header-app">
                <div>
                    <h2 class="modal-title-app" id="suppliersTitle">Suppliers</h2>
                    <p class="modal-subtitle-app">Contacts you create restock orders with.</p>
                </div>
                <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-body-app">

                <!-- Supplier list -->
                <?php if (empty($suppliers)): ?>
                    <div class="supplier-empty-add">
                        <p class="supplier-empty-text">
                            No suppliers yet — add your first one below.
                        </p>
                    </div>
                <?php else: ?>
                    <ul class="supplier-list">
                        <?php foreach ($suppliers as $s):
                            $sid   = (int) $s['id'];
                            $p     = $pick($s, ['contact_person'], '');
                            $pMail = $pick($s, ['email'], '');
                            $pPh   = $pick($s, ['phone'], '');
                            $meta  = [];
                            if ($p !== '')     $meta[] = $p;
                            if ($pMail !== '') $meta[] = $pMail;
                        ?>
                            <li class="supplier-item">
                                <div class="supplier-copy">
                                    <p class="supplier-name"><?= $e($s['name']) ?></p>
                                    <?php if ($meta !== []): ?>
                                        <p class="supplier-meta"><?= $e(implode(' · ', $meta)) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2 flex-shrink-0">
                                    <button type="button" class="icon-btn" aria-label="Edit supplier"
                                            data-bs-toggle="modal" data-bs-target="#supplierEditModal"
                                            data-supplier-id="<?= $sid ?>"
                                            data-name="<?= $e($s['name']) ?>"
                                            data-contact-person="<?= $e($p) ?>"
                                            data-email="<?= $e($pMail) ?>"
                                            data-phone="<?= $e($pPh) ?>"
                                            data-address="<?= $e($pick($s, ['address'], '')) ?>">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="icon-btn" aria-label="Delete supplier"
                                            data-bs-toggle="modal" data-bs-target="#supplierDeleteModal"
                                            data-supplier-id="<?= $sid ?>"
                                            data-name="<?= $e($s['name']) ?>">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="supplier-divider"></div>

                <!-- Add supplier form -->
                <form method="post" action="/suppliers/create" id="supplierCreateForm" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                    <input type="hidden" name="return_to" id="supplierCreateReturnTo" value="suppliers">

                    <h3 class="supplier-form-title">Add a supplier</h3>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="supplier-name">Name <span class="req">*</span></label>
                            <input class="form-control-app" type="text" id="supplier-name" name="name"
                                   required maxlength="150" placeholder="e.g. TechSource Trading">
                        </div>
                    </div>
                    <div class="form-row two-col">
                        <div>
                            <label class="form-label-app" for="supplier-contact">Contact person</label>
                            <input class="form-control-app" type="text" id="supplier-contact" name="contact_person"
                                   maxlength="150" placeholder="Optional">
                        </div>
                        <div>
                            <label class="form-label-app" for="supplier-phone">Phone</label>
                            <input class="form-control-app" type="text" id="supplier-phone" name="phone"
                                   maxlength="30" placeholder="Optional">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="supplier-email">Email</label>
                            <input class="form-control-app" type="email" id="supplier-email" name="email"
                                   maxlength="255" placeholder="Optional">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="supplier-address">Address</label>
                            <input class="form-control-app" type="text" id="supplier-address" name="address"
                                   maxlength="500" placeholder="Optional">
                        </div>
                    </div>
                </form>

            </div>

            <div class="modal-footer-app">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn-primary-app" form="supplierCreateForm">
                    <i class="bi bi-check2" aria-hidden="true"></i>Add supplier
                </button>
            </div>
        </div>
    </div>
</div>

/* =========================================================
   ORDERS — suppliers (manage modal chaining, edit/delete
   field population, ?modal=auto-open after redirects)
   Loaded by pages/user/orders/index.php
   ========================================================= */
(function () {
    'use strict';

    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

    var suppliersModal = document.getElementById('suppliersModal');
    var newOrderModal  = document.getElementById('newOrderModal');
    if (!suppliersModal) return;

    var suppliers   = bootstrap.Modal.getOrCreateInstance(suppliersModal);
    var newOrder    = newOrderModal ? bootstrap.Modal.getOrCreateInstance(newOrderModal) : null;
    /* Which modal the suppliers modal was opened from.
       null = opened from the page header. */
    var openedFrom  = null;
    /* Whether a form submit is in flight (suppresses re-open chains). */
    var submitted   = false;

    /* ---------- Open helpers ---------- */

    function openSuppliers(returnTo) {
        openedFrom = returnTo === 'new-order' ? 'new-order' : null;
        var rt = document.getElementById('supplierCreateReturnTo');
        if (rt) rt.value = returnTo === 'new-order' ? 'new-order' : 'suppliers';
        suppliers.show();
    }

    /* "Suppliers" button in the page header */
    var manageBtn = document.getElementById('manageSuppliersBtn');
    if (manageBtn) {
        manageBtn.addEventListener('click', function () { openSuppliers('suppliers'); });
    }

    /* "Add a supplier" button inside the new-order modal */
    document.querySelectorAll('[data-suppliers-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var returnTo = btn.getAttribute('data-return-to') === 'new-order' ? 'new-order' : 'suppliers';
            if (newOrder) newOrder.hide();
            openSuppliers(returnTo);
        });
    });

    /* Create form submit → page navigates via the POST;
       still hide for form validation errors that stay inline. */
    var createForm = document.getElementById('supplierCreateForm');
    if (createForm) {
        createForm.addEventListener('submit', function () { submitted = true; });
    }

    /* Close without submitting → re-open the opener modal. */
    suppliersModal.addEventListener('hidden.bs.modal', function () {
        if (submitted) {
            submitted = false;
            openedFrom = null;
            return;
        }
        if (openedFrom === 'new-order' && newOrder) newOrder.show();
        openedFrom = null;
    });

    /* ---------- Edit modal — populate fields ---------- */
    var editModalEl = document.getElementById('supplierEditModal');
    if (editModalEl) {
        var editModal   = bootstrap.Modal.getOrCreateInstance(editModalEl);
        var editForm    = editModalEl.querySelector('form');

        editModalEl.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;

            /* Getting here from the suppliers list — slide the
               list modal back so only one backdrop is active. */
            suppliers.hide();
            openedFrom = null;

            document.getElementById('supplier-edit-id').value      = btn.getAttribute('data-supplier-id') || '';
            document.getElementById('supplier-edit-name').value    = btn.getAttribute('data-name') || '';
            document.getElementById('supplier-edit-contact').value = btn.getAttribute('data-contact-person') || '';
            document.getElementById('supplier-edit-phone').value   = btn.getAttribute('data-phone') || '';
            document.getElementById('supplier-edit-email').value   = btn.getAttribute('data-email') || '';
            document.getElementById('supplier-edit-address').value = btn.getAttribute('data-address') || '';
        });

        editForm.addEventListener('submit', function () {
            submitted = true;
        });

        editModalEl.addEventListener('hidden.bs.modal', function () {
            if (submitted) {
                submitted = false;
                openedFrom = null;
                return;
            }
            /* Closed via Cancel — go back to the suppliers list */
            suppliers.show();
        });
    }

    /* ---------- Delete modal — populate fields ---------- */
    var deleteModalEl = document.getElementById('supplierDeleteModal');
    if (deleteModalEl) {
        var deleteModal = bootstrap.Modal.getOrCreateInstance(deleteModalEl);
        var deleteForm  = deleteModalEl.querySelector('form');

        deleteModalEl.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;

            /* Getting here from the suppliers list — slide the
               list modal back so only one backdrop is active. */
            suppliers.hide();
            openedFrom = null;

            document.getElementById('supplier-delete-id').value = btn.getAttribute('data-supplier-id') || '';
            document.getElementById('supplier-delete-name').textContent =
                btn.getAttribute('data-name') || 'this supplier';
        });

        deleteForm.addEventListener('submit', function () { submitted = true; });

        deleteModalEl.addEventListener('hidden.bs.modal', function () {
            if (submitted) {
                submitted = false;
                openedFrom = null;
                return;
            }
            suppliers.show();
        });
    }

    /* ---------- Auto-open after POST redirects (?modal=…) ---------- */
    var params = new URLSearchParams(window.location.search);
    var modalParam = params.get('modal');
    if (modalParam === 'suppliers') {
        openSuppliers('suppliers');
    } else if (modalParam === 'new-order' && newOrder) {
        newOrder.show();
    }

    /* Strip the ?modal= param so refresh doesn't re-open modals */
    if (modalParam) {
        params.delete('modal');
        var qs = params.toString();
        window.history.replaceState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
    }
})();

/* =========================================================
   ORDERS — modals (new order + detail auto-open on ?id=X)
   Loaded by pages/user/orders/index.php
   ========================================================= */

/* =========================================================
   NEW ORDER MODAL
   ========================================================= */
(function () {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
    var modalEl = document.getElementById('newOrderModal');
    var addBtn  = document.getElementById('addOrderBtn');
    if (!modalEl || !addBtn) return;
    var modal = new bootstrap.Modal(modalEl);
    addBtn.addEventListener('click', function () { modal.show(); });
})();

/* =========================================================
   ORDER DETAIL MODAL — auto-opens on ?id=X
   ========================================================= */
(function () {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
    var modalEl = document.getElementById('orderModal');
    if (!modalEl) return;
    var modal = new bootstrap.Modal(modalEl);
    modal.show();

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (new URLSearchParams(window.location.search).has('id')) {
            window.location.href = '/orders';
        }
    });
})();

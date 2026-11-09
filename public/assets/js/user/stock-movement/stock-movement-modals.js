/* =========================================================
   STOCK MOVEMENTS — new movement modal (Bootstrap)
   Loaded by pages/user/stock-movement/index.php
   ========================================================= */
(function () {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
    var modalEl = document.getElementById('newMovementModal');
    var addBtn  = document.getElementById('addMovementBtn');
    if (!modalEl || !addBtn) return;
    var modal = new bootstrap.Modal(modalEl);
    addBtn.addEventListener('click', function () { modal.show(); });
})();

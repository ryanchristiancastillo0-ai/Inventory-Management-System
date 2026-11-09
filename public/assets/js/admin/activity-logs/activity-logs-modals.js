/* =========================================================
   ACTIVITY LOGS — detail modal
   Populates #logDetailModal from the trigger button's data-* attrs.
   ========================================================= */
(function () {
    var modal = document.getElementById('logDetailModal');
    if (!modal) return;

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '—';
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;

        setText('d-id',      '#' + (btn.getAttribute('data-log-id') || '—'));
        setText('d-action',  btn.getAttribute('data-log-action'));
        setText('d-actor',   btn.getAttribute('data-log-actor'));
        setText('d-email',   btn.getAttribute('data-log-email'));
        setText('d-target',  btn.getAttribute('data-log-target'));
        setText('d-details', btn.getAttribute('data-log-details'));

        var when = btn.getAttribute('data-log-when') || '';
        var rel  = btn.getAttribute('data-log-relative') || '';
        setText('d-when', when + (rel && when ? ' · ' + rel : ''));
    });
})();

/* =========================================================
   ADMIN REPORTS — manage modal
   Populates #manageReportModal from the trigger button.
   ========================================================= */
(function () {
    var modal = document.getElementById('manageReportModal');
    if (!modal) return;

    function setText(id, value, fallback) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || fallback || '—';
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;

        document.getElementById('mr-id').value = btn.getAttribute('data-report-id') || '';

        setText('mr-subject',  btn.getAttribute('data-report-subject'));
        setText('mr-reporter', btn.getAttribute('data-report-reporter'));
        setText('mr-when',     btn.getAttribute('data-report-when'));
        setText('mr-desc',     btn.getAttribute('data-report-desc'), 'No description provided.');

        var status = (btn.getAttribute('data-report-status') || 'PENDING').toUpperCase();
        var sel = document.getElementById('mr-status');
        if (sel) {
            var found = false;
            Array.prototype.forEach.call(sel.options, function (opt) {
                if (opt.value === status) { opt.selected = true; found = true; }
            });
            if (!found) {
                /* Normalise common variants to the controller's expected values */
                var map = { 'OPEN': 'PENDING', 'NEW': 'PENDING', 'PROCESSING': 'IN_PROGRESS', 'REVIEWING': 'IN_PROGRESS', 'CLOSED': 'RESOLVED', 'DONE': 'RESOLVED', 'COMPLETED': 'RESOLVED' };
                if (map[status] && sel.querySelector('option[value="' + map[status] + '"]')) {
                    sel.value = map[status];
                } else {
                    sel.value = 'PENDING';
                }
            }
        }

        var resp = document.getElementById('mr-response');
        if (resp) resp.value = btn.getAttribute('data-report-response') || '';
    });
})();

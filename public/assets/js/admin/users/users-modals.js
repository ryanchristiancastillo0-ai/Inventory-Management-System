/* =========================================================
   ADMIN USERS — modals (edit + status field population)
   Loaded by pages/admin/users/index.php
   ========================================================= */

/* =========================================================
   EDIT MODAL — populate fields from the clicked button
   ========================================================= */
(function () {
    var modal = document.getElementById('editUserModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;

        var id       = btn.getAttribute('data-user-id') || '';
        var username = btn.getAttribute('data-username') || '';
        var email    = btn.getAttribute('data-email') || '';
        var role     = btn.getAttribute('data-role') || 'user';

        document.getElementById('edit-id').value       = id;
        document.getElementById('edit-username').value = username;
        document.getElementById('edit-email').value    = email;
        document.getElementById('edit-password').value = '';
        document.getElementById('edit-role').value     = (role === 'admin') ? 'ADMIN' : 'USER';
    });
})();

/* =========================================================
   STATUS MODAL — populate fields from the clicked button
   ========================================================= */
(function () {
    var modal = document.getElementById('userStatusModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;

        var makeActive = btn.getAttribute('data-next-status') !== '0';

        document.getElementById('status-id').value        = btn.getAttribute('data-user-id') || '';
        document.getElementById('status-is-active').value = makeActive ? '1' : '0';
        document.getElementById('status-username').textContent =
            btn.getAttribute('data-username') || 'this user';

        var title  = document.getElementById('userStatusTitle');
        var verb   = document.getElementById('status-verb');
        var label  = document.getElementById('status-submit-label');
        var submit = document.getElementById('status-submit');
        var subtxt = document.getElementById('userStatusSubtitle');
        var note   = document.getElementById('status-note');

        if (makeActive) {
            title.textContent = 'Activate user';
            verb.textContent = 'Activate';
            label.textContent = 'Activate';
            submit.style.background = '';
            subtxt.textContent = 'They will be able to log in again.';
            note.textContent = 'The account and its data stay intact and can be deactivated at any time.';
        } else {
            title.textContent = 'Deactivate user';
            verb.textContent = 'Deactivate';
            label.textContent = 'Deactivate';
            submit.style.background = 'var(--danger)';
            subtxt.textContent = 'They will no longer be able to log in.';
            note.textContent = 'The account and its data stay intact and can be re-activated at any time.';
        }
    });
})();

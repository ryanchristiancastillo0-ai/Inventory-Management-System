/* =========================================================
   STOCK MOVEMENTS — topbar UI (toast + notifications user menu)
   Dropdowns are native Bootstrap — no custom manager needed.
   Loaded by pages/user/stock-movement/index.php
   ========================================================= */

/* =========================================================
   TOAST
   ========================================================= */
(function () {
    var stack = document.getElementById('toastStack');
    window.showToast = function (message, icon) {
        if (!stack) return;
        var t = document.createElement('div');
        t.className = 'toast-app';
        t.innerHTML = '<i class="bi ' + (icon || 'bi-check2-circle') + '" aria-hidden="true"></i><span></span>';
        t.querySelector('span').textContent = message;
        stack.appendChild(t);
        setTimeout(function () {
            t.classList.add('toast-out');
            t.addEventListener('animationend', function () { t.remove(); }, { once: true });
        }, 3200);
    };
})();

/* =========================================================
   NOTIFICATIONS + USER MENU (Bootstrap dropdowns)
   ========================================================= */
(function () {
    document.querySelectorAll('[data-notif]').forEach(function (item) {
        item.addEventListener('click', function () {
            var t = item.querySelector('.notif-title');
            window.showToast(t ? t.textContent : 'Notification', 'bi-bell');
        });
    });
    document.querySelectorAll('#userMenuBtn + .dropdown-menu [data-user-action], .dropdown-menu [data-user-action]').forEach(function (item) {
        item.addEventListener('click', function () {
            var action = item.getAttribute('data-user-action');
            window.showToast(
                action === 'logout' ? 'Signed out (demo)' : 'Opening ' + item.textContent.trim().toLowerCase() + '…',
                action === 'logout' ? 'bi-box-arrow-right' : 'bi-arrow-right'
            );
        });
    });
    var markAll = document.getElementById('markAllRead');
    if (markAll) {
        markAll.addEventListener('click', function () {
            var dot = document.getElementById('notifDot');
            if (dot) dot.style.display = 'none';
            window.showToast('All notifications marked as read', 'bi-check2-all');
        });
    }
})();

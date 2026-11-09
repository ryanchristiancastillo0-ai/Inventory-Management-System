/* =========================================================
   PRODUCTS / INVENTORY — topbar UI (toast + dropdowns + notifications + user menu)
   Loaded by pages/user/products/index.php and inventory/index.php
   ========================================================= */

/* =========================================================
   TOAST HELPER
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
            t.addEventListener('animationend', function () { t.remove(); });
        }, 2600);
    };
})();

/* =========================================================
   DROPDOWN MANAGER
   ========================================================= */
(function () {
    function closeAll() {
        document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
        document.querySelectorAll('[data-dropdown-toggle]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    }
    document.querySelectorAll('[data-dropdown-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var menu = document.getElementById(btn.dataset.dropdownToggle);
            if (!menu) return;
            var wasOpen = menu.classList.contains('is-open');
            closeAll();
            if (!wasOpen) { menu.classList.add('is-open'); btn.setAttribute('aria-expanded', 'true'); }
        });
    });
    document.querySelectorAll('.dropdown-menu-app').forEach(function (m) {
        m.addEventListener('click', function (e) { e.stopPropagation(); });
    });
    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });
})();

/* =========================================================
   NOTIFICATIONS + USER MENU
   ========================================================= */
(function () {
    document.querySelectorAll('[data-notif]').forEach(function (item) {
        item.addEventListener('click', function () {
            var t = item.querySelector('.notif-title');
            window.showToast(t ? t.textContent : 'Notification', 'bi-bell');
        });
    });
    document.querySelectorAll('#userMenu [data-user-action]').forEach(function (item) {
        item.addEventListener('click', function () {
            var action = item.dataset.userAction;
            document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            if (action === 'logout') window.showToast('Signed out (demo)', 'bi-box-arrow-right');
            else window.showToast('Opening ' + item.textContent.trim().toLowerCase() + '…', 'bi-arrow-right');
        });
    });
    var markAll = document.getElementById('markAllRead');
    if (markAll) {
        markAll.addEventListener('click', function () {
            var dot = document.getElementById('notifDot');
            if (dot) dot.style.display = 'none';
            document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            window.showToast('All notifications marked as read', 'bi-check2-all');
        });
    }
})();

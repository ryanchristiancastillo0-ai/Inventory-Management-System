/* =========================================================
   ORDERS — topbar UI (toast + delegated dropdowns + notifications)
   Loaded by pages/user/orders/index.php
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
   DROPDOWNS — robust, delegated, viewport-aware.
   ========================================================= */
(function () {
    'use strict';

    var openMenuEl = null;

    function resetMenu(menu) {
        if (!menu) return;
        menu.classList.remove('is-open');
        menu.style.position = '';
        menu.style.top = '';
        menu.style.left = '';
        menu.style.right = '';
        menu.style.bottom = '';
        menu.style.visibility = '';
        menu.style.display = '';
        menu.style.zIndex = '';
    }

    function closeAll() {
        if (openMenuEl) { resetMenu(openMenuEl); openMenuEl = null; }
        document.querySelectorAll('.dropdown-menu-app.is-open').forEach(resetMenu);
        document.querySelectorAll('[data-dropdown-toggle][aria-expanded="true"]').forEach(function (b) {
            b.setAttribute('aria-expanded', 'false');
        });
    }

    function openDropdown(btn, menu) {
        closeAll();

        // 1) Show menu so it has layout; hide visually while we measure.
        menu.classList.add('is-open');
        menu.style.visibility = 'hidden';

        // 2) Fallback: if the layout didn't define display for `.is-open`,
        //    force it so the menu can be measured and shown.
        if (getComputedStyle(menu).display === 'none') {
            menu.style.display = 'block';
        }

        var mw = menu.offsetWidth  || 200;
        var mh = menu.offsetHeight || 120;

        var pad = 8, gap = 6;
        var vw  = window.innerWidth;
        var vh  = window.innerHeight;
        var r   = btn.getBoundingClientRect();

        var top  = r.bottom + gap;
        var left = r.right - mw;

        // Flip up if it would overflow the viewport bottom.
        if (top + mh > vh - pad) {
            var above = r.top - mh - gap;
            top = above >= pad ? above : Math.max(pad, vh - mh - pad);
        }
        if (top < pad) top = pad;

        // Clamp horizontally.
        if (left < pad) left = pad;
        if (left + mw > vw - pad) left = vw - mw - pad;
        if (left < pad) left = pad;

        menu.style.position = 'fixed';
        menu.style.top      = top + 'px';
        menu.style.left     = left + 'px';
        menu.style.right    = 'auto';
        menu.style.bottom   = 'auto';
        menu.style.visibility = '';

        btn.setAttribute('aria-expanded', 'true');
        openMenuEl = menu;
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-dropdown-toggle]');
        if (btn) {
            e.preventDefault();
            var menu = document.getElementById(btn.getAttribute('data-dropdown-toggle'));
            if (!menu) return;
            var wasOpen = menu.classList.contains('is-open');
            closeAll();
            if (!wasOpen) openDropdown(btn, menu);
            return;
        }

        // Any click inside an open menu: let the item's own handler fire,
        // then close (keeps menus from getting "stuck").
        if (e.target.closest('.dropdown-menu-app')) {
            closeAll();
            return;
        }

        closeAll();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAll();
    });

    // Close on scroll (capture so we catch any scrollable ancestor) and resize,
    // since a fixed-positioned menu would otherwise detach from its trigger.
    window.addEventListener('scroll', closeAll, { passive: true, capture: true });
    window.addEventListener('resize', closeAll, { passive: true });
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

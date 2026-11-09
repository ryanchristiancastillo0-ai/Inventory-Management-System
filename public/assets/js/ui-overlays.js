/* =========================================================
   UI OVERLAYS — shared row-action menu positioning
   ---------------------------------------------------------
   Tables live inside `.panel` (overflow: hidden) and
   `.table-responsive` (overflow-x: auto), so an `absolute`
   menu gets clipped by those boxes. While a row menu is open
   it is switched to viewport-fixed coordinates instead.

   Supports both menu systems used in the app:
     - custom    : .dropdown-menu-app + [data-dropdown-toggle]
     - Bootstrap : .dropdown-menu     + [data-bs-toggle="dropdown"]
   ========================================================= */
(function () {
    'use strict';

    var EDGE   = 8;  /* minimum gap to the viewport edge */
    var OFFSET = 6;  /* gap between trigger and menu      */

    /* Menus we manage (only row menus, never filter bars) */
    var registry = [];

    function isCustom(menu) {
        return menu.classList.contains('dropdown-menu-app');
    }

    function isOpen(menu) {
        return isCustom(menu)
            ? menu.classList.contains('is-open')
            : menu.classList.contains('show');
    }

    function floatingClass(menu) {
        return isCustom(menu) ? 'is-floating' : 'is-row-floating';
    }

    function isFloating(menu) {
        return menu.classList.contains('is-floating') ||
               menu.classList.contains('is-row-floating');
    }

    function triggerFor(menu) {
        var box = menu.parentElement;
        if (!box) return null;
        return isCustom(menu)
            ? box.querySelector('[data-dropdown-toggle]')
            : box.querySelector('[data-bs-toggle="dropdown"]');
    }

    function place(menu, trigger) {
        if (!trigger) return;

        var r = trigger.getBoundingClientRect();
        var w = menu.offsetWidth;
        var h = menu.offsetHeight;
        if (!w || !h) return;

        var vw = window.innerWidth;
        var vh = window.innerHeight;

        /* Right-align with the trigger, then keep it on screen. */
        var left = r.right - w;
        if (left + w > vw - EDGE) left = vw - w - EDGE;
        if (left < EDGE) left = EDGE;

        /* Prefer opening below; flip above when there is no room. */
        var top = r.bottom + OFFSET;
        if (top + h > vh - EDGE) {
            var above = r.top - h - OFFSET;
            top = above >= EDGE ? above : Math.max(EDGE, vh - h - EDGE);
        }

        if (isCustom(menu)) {
            menu.style.top   = top + 'px';
            menu.style.left  = left + 'px';
            menu.style.right = 'auto';
        } else {
            menu.style.setProperty('--row-menu-top', top + 'px');
            menu.style.setProperty('--row-menu-left', left + 'px');
        }
    }

    function clear(menu) {
        menu.classList.remove('is-floating', 'is-row-floating');
        menu.style.removeProperty('--row-menu-top');
        menu.style.removeProperty('--row-menu-left');
        if (isCustom(menu)) {
            menu.style.top   = '';
            menu.style.left  = '';
            menu.style.right = '';
        }
    }

    function sync(menu, trigger) {
        if (!trigger) return;
        if (isOpen(menu)) {
            var cls = floatingClass(menu);
            if (!menu.classList.contains(cls)) menu.classList.add(cls);
            place(menu, trigger);

            /* The dashboard sections run a short fade-up animation. While a
               transform is active it becomes the containing block for fixed
               elements, so re-place once the animation has finished. */
            if (!menu.__reflowPending) {
                menu.__reflowPending = true;
                window.setTimeout(function () {
                    menu.__reflowPending = false;
                    if (isOpen(menu)) place(menu, trigger);
                }, 600);
            }
        } else if (isFloating(menu)) {
            clear(menu);
        }
    }

    function watch(menu) {
        /* Filter-bar / topbar dropdowns keep their normal behaviour. */
        if (!menu.closest('table')) return;

        var trigger = triggerFor(menu);
        if (!trigger) return;

        registry.push({ menu: menu, trigger: trigger });

        new MutationObserver(function () {
            sync(menu, trigger);
        }).observe(menu, { attributes: true, attributeFilter: ['class'] });
    }

    function repositionOpen() {
        registry.forEach(function (entry) {
            if (isOpen(entry.menu)) place(entry.menu, entry.trigger);
        });
    }

    function init() {
        document.querySelectorAll('.dropdown-menu-app, .dropdown-menu').forEach(watch);

        window.addEventListener('resize', repositionOpen);
        document.addEventListener('scroll', repositionOpen, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

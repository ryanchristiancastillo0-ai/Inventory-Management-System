(function () {
    var stack = document.getElementById('toastStack');
    window.showToast = function (message, icon) {
        if (!stack) return;
        var toast = document.createElement('div');
        toast.className = 'toast-app';
        toast.innerHTML = '<i class="bi ' + (icon || 'bi-check2-circle') + '" aria-hidden="true"></i>' + message;
        stack.appendChild(toast);
        setTimeout(function () {
            toast.classList.add('toast-out');
            toast.addEventListener('animationend', function () { toast.remove(); });
        }, 2600);
    };
})();

/* =========================================================
   COMPONENT: Dropdown manager
   ========================================================= */
(function () {
    function closeAll() {
        document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) {
            m.classList.remove('is-open');
        });
        document.querySelectorAll('[data-dropdown-toggle]').forEach(function (b) {
            b.setAttribute('aria-expanded', 'false');
        });
    }

    document.querySelectorAll('[data-dropdown-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var targetId = btn.dataset.dropdownToggle;
            var menu = document.getElementById(targetId);
            if (!menu) return;
            var isOpen = menu.classList.contains('is-open');
            closeAll();
            if (!isOpen) {
                menu.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.querySelectorAll('.dropdown-menu-app').forEach(function (menu) {
        menu.addEventListener('click', function (e) { e.stopPropagation(); });
    });

    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAll();
    });
})();

/* =========================================================
   COMPONENT: Notifications
   ========================================================= */
(function () {
    document.querySelectorAll('[data-notif]').forEach(function (item) {
        item.addEventListener('click', function () {
            var title = item.querySelector('.notif-title');
            window.showToast(title ? title.textContent : 'Notification', 'bi-bell');
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

/* =========================================================
   COMPONENT: User menu
   ========================================================= */
(function () {
    document.querySelectorAll('#userMenu [data-user-action]').forEach(function (item) {
        item.addEventListener('click', function () {
            var action = item.dataset.userAction;
            document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            if (action === 'logout') {
                // Form submission handles logout
            } else {
                var label = item.textContent.trim();
                window.showToast('Opening ' + label.toLowerCase() + '…', 'bi-arrow-right');
            }
        });
    });
})();

/* =========================================================
   COMPONENT: Activity table — search + filter
   ========================================================= */
(function () {
    var input = document.getElementById('activitySearch');
    var rows = document.querySelectorAll('#activityBody tr');
    var empty = document.getElementById('noResults');
    var tabs = document.querySelectorAll('.segmented-item');
    var currentFilter = 'all';
    var currentSearch = '';

    function applyFilters() {
        var visible = 0;
        rows.forEach(function (row) {
            var typeMatch = currentFilter === 'all' || row.dataset.type === currentFilter;
            var searchMatch = !currentSearch || row.textContent.toLowerCase().indexOf(currentSearch) !== -1;
            var show = typeMatch && searchMatch;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        empty.style.display = visible ? 'none' : 'block';
        var label = document.getElementById('showingLabel');
        if (label) {
            if (currentFilter === 'all' && !currentSearch) {
                label.textContent = 'Showing ' + rows.length + ' of ' + rows.length + ' movements';
            } else {
                label.textContent = 'Showing ' + visible + ' of ' + rows.length + ' matched movements';
            }
        }
    }

    if (input) {
        input.addEventListener('input', function () {
            currentSearch = input.value.trim().toLowerCase();
            applyFilters();
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            currentFilter = tab.dataset.filter;
            applyFilters();
        });
    });

    applyFilters();
})();

/* =========================================================
   COMPONENT: Keyboard shortcut âŒ˜K / Ctrl+K
   ========================================================= */
(function () {
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            var search = document.getElementById('activitySearch');
            if (search) search.focus();
        }
    });
})();

/* =========================================================
   COMPONENT: Export button (shared CSV/Excel module)
   ========================================================= */
(function () {
    var btn = document.getElementById('exportBtn');
    if (!btn || !window.AppExport) return;

    function cellText(cells, index) {
        return cells[index] ? cells[index].textContent.trim() : '';
    }

    window.AppExport.bind(btn, {
        filename: 'inventory-activity',
        columns: ['Product', 'SKU', 'Category', 'Activity', 'Quantity', 'Date', 'Status'],
        getRows: function () {
            return Array.prototype.filter.call(
                document.querySelectorAll('#activityBody tr'),
                function (row) { return row.style.display !== 'none'; }
            ).map(function (row) {
                var cells = row.querySelectorAll('td');
                return [
                    (cells[0].querySelector('.product-name') || cells[0]).textContent.trim(),
                    cellText(cells, 1),
                    cellText(cells, 2),
                    cellText(cells, 3),
                    cellText(cells, 4),
                    cellText(cells, 5),
                    cellText(cells, 6)
                ];
            });
        }
    });
})();
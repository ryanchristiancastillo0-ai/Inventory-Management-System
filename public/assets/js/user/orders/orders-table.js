/* =========================================================
   ORDERS TABLE — search, filter, pagination, row actions, ⌘K
   Loaded by pages/user/orders/index.php
   ========================================================= */
(function () {
    var body = document.getElementById('ordersBody');
    if (!body) return;

    var empty        = document.getElementById('noResults');
    var showing      = document.getElementById('showingLabel');
    var countEl      = document.getElementById('filterCount');
    var pagination   = document.getElementById('pagination');
    var filterSearch = document.getElementById('filterSearch');
    var topSearch    = document.getElementById('ordersSearch');
    var statusLabel  = document.getElementById('statusFilterLabel');
    var clearBtn     = document.getElementById('clearFiltersBtn');

    var PAGE_SIZE = 12;
    var currentSearch = '';
    var currentStatus = '';
    var currentPage   = 1;

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr'));

    function filteredRows() {
        return allRows.filter(function (row) {
            var statusOk = !currentStatus || row.getAttribute('data-status') === currentStatus;
            var haystack = (row.getAttribute('data-order-number') || '') + ' ' + (row.getAttribute('data-supplier-name') || '');
            var searchOk = !currentSearch || haystack.toLowerCase().indexOf(currentSearch) !== -1;
            return statusOk && searchOk;
        });
    }

    function syncInputs(value) {
        if (filterSearch && filterSearch.value !== value) filterSearch.value = value;
        if (topSearch    && topSearch.value    !== value) topSearch.value    = value;
    }

    function setSearch(value) {
        currentSearch = String(value || '').trim().toLowerCase();
        currentPage = 1;
        syncInputs(value);
        render();
    }

    function render() {
        var matched = filteredRows();
        var total = matched.length;
        var pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > pages) currentPage = pages;
        var start = (currentPage - 1) * PAGE_SIZE;
        var end   = start + PAGE_SIZE;

        allRows.forEach(function (row) { row.style.display = 'none'; });
        matched.slice(start, end).forEach(function (row) { row.style.display = ''; });

        if (empty) empty.style.display = total === 0 ? 'block' : 'none';
        if (countEl) countEl.textContent = total + (total === 1 ? ' order' : ' orders');
        if (clearBtn) clearBtn.hidden = !(currentSearch || currentStatus);

        if (showing) {
            showing.textContent = total === 0
                ? 'No orders'
                : 'Showing ' + (start + 1) + '–' + Math.min(end, total) + ' of ' + total;
        }

        renderPagination(pages);
    }

    function renderPagination(pages) {
        if (!pagination) return;
        pagination.innerHTML = '';

        function btn(label, page, opts) {
            opts = opts || {};
            var b = document.createElement('button');
            b.type = 'button';
            b.innerHTML = label;
            if (opts.active) { b.classList.add('active'); b.setAttribute('aria-current', 'page'); }
            if (opts.disabled) b.disabled = true;
            if (opts.ariaLabel) b.setAttribute('aria-label', opts.ariaLabel);
            b.addEventListener('click', function () { currentPage = page; render(); });
            return b;
        }

        pagination.appendChild(btn('<i class="bi bi-chevron-left" aria-hidden="true"></i>', currentPage - 1, {
            disabled: currentPage === 1, ariaLabel: 'Previous page'
        }));

        var range = [];
        if (pages <= 7) {
            for (var i = 1; i <= pages; i++) range.push(i);
        } else {
            range.push(1);
            if (currentPage > 3) range.push('…');
            for (var j = Math.max(2, currentPage - 1); j <= Math.min(pages - 1, currentPage + 1); j++) range.push(j);
            if (currentPage < pages - 2) range.push('…');
            range.push(pages);
        }

        range.forEach(function (p) {
            if (p === '…') {
                var el = document.createElement('span');
                el.textContent = '…';
                el.style.cssText = 'display:inline-flex;width:28px;height:28px;align-items:center;justify-content:center;color:var(--text-tertiary);font-size:12px;';
                pagination.appendChild(el);
            } else {
                pagination.appendChild(btn(String(p), p, { active: p === currentPage }));
            }
        });

        pagination.appendChild(btn('<i class="bi bi-chevron-right" aria-hidden="true"></i>', currentPage + 1, {
            disabled: currentPage === pages, ariaLabel: 'Next page'
        }));
    }

    if (filterSearch) {
        filterSearch.addEventListener('input', function () { setSearch(filterSearch.value); });
    }
    if (topSearch) {
        topSearch.addEventListener('input', function () { setSearch(topSearch.value); });
    }

    function markFilterSelection(item) {
        var menu = item.closest('.dropdown-menu-app');
        if (!menu) return;
        menu.querySelectorAll('.dropdown-item-app.is-selected').forEach(function (el) {
            el.classList.remove('is-selected');
        });
        item.classList.add('is-selected');
    }

    document.querySelectorAll('#statusFilterMenu [data-status]').forEach(function (item, index) {
        if (index === 0) item.classList.add('is-selected');
        item.addEventListener('click', function () {
            currentStatus = item.getAttribute('data-status') || '';
            if (statusLabel) statusLabel.textContent = item.textContent.trim();
            markFilterSelection(item);
            currentPage = 1;
            render();
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            currentStatus = '';
            if (statusLabel) statusLabel.textContent = 'All statuses';
            document.querySelectorAll('#statusFilterMenu [data-status]').forEach(function (el, i) {
                el.classList.toggle('is-selected', i === 0);
            });
            setSearch('');
        });
    }

    /* Row actions */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-row-action]');
        if (!btn) return;
        var row = btn.closest('tr.order-row');
        if (!row) return;
        var action = btn.getAttribute('data-row-action');

        if (action === 'view') {
            window.location.href = '/orders?id=' + row.getAttribute('data-order-id');
        } else if (action === 'copy') {
            var num = row.getAttribute('data-order-number');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(num).then(function () {
                    window.showToast('Order number copied', 'bi-clipboard-check');
                }).catch(function () {
                    window.showToast('Order: ' + num, 'bi-clipboard');
                });
            } else {
                window.showToast('Order: ' + num, 'bi-clipboard');
            }
        }
    });

    /* ⌘K / Ctrl+K focuses the in-page filter */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (filterSearch) filterSearch.focus();
        }
    });

    render();
})();

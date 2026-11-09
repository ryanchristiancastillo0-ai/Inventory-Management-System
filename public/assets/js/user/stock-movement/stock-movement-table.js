/* =========================================================
   STOCK MOVEMENTS TABLE — search, filter, pagination
   Loaded by pages/user/stock-movement/index.php
   ========================================================= */
(function () {
    var body = document.getElementById('movementsBody');
    if (!body) return;

    var empty        = document.getElementById('noResults');
    var showing      = document.getElementById('showingLabel');
    var countEl      = document.getElementById('filterCount');
    var pagination   = document.getElementById('pagination');
    var filterSearch = document.getElementById('filterSearch');
    var topSearch    = document.getElementById('topSearch');
    var typeLabel    = document.getElementById('typeFilterLabel');
    var typeMenu     = document.getElementById('typeFilterMenu');
    var clearBtn     = document.getElementById('clearFiltersBtn');

    var PAGE_SIZE = 12;
    var currentSearch = '';
    var currentType   = '';
    var currentPage   = 1;

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr'));

    function filteredRows() {
        return allRows.filter(function (row) {
            var rowType = (row.getAttribute('data-movement-type') || '').toUpperCase();
            var typeOk  = !currentType || rowType === currentType;
            var haystack = [
                row.getAttribute('data-product-name') || '',
                row.getAttribute('data-product-sku')  || '',
                row.getAttribute('data-remarks')      || '',
                row.getAttribute('data-movement-id')  || ''
            ].join(' ').toLowerCase();
            var searchOk = !currentSearch || haystack.indexOf(currentSearch) !== -1;
            return typeOk && searchOk;
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

    function setType(type, label) {
        currentType = String(type || '').toUpperCase();
        if (typeLabel) typeLabel.textContent = label || 'All types';

        if (typeMenu) {
            typeMenu.querySelectorAll('[data-type]').forEach(function (el) {
                var on = (el.getAttribute('data-type') || '').toUpperCase() === currentType;
                el.classList.toggle('active', on);
                if (on) el.setAttribute('aria-current', 'true');
                else el.removeAttribute('aria-current');
            });
        }

        currentPage = 1;
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
        if (countEl) countEl.textContent = total + (total === 1 ? ' movement' : ' movements');
        if (clearBtn) clearBtn.hidden = !(currentSearch || currentType);

        if (showing) {
            showing.textContent = total === 0
                ? 'No movements'
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

    if (filterSearch) filterSearch.addEventListener('input', function () { setSearch(filterSearch.value); });
    if (topSearch)    topSearch.addEventListener('input',    function () { setSearch(topSearch.value); });

    /* Type filter — one delegated listener on the menu (Bootstrap closes it on item click) */
    if (typeMenu) {
        typeMenu.addEventListener('click', function (e) {
            var item = e.target.closest('[data-type]');
            if (!item) return;
            setType(item.getAttribute('data-type'), item.textContent.trim());
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            setType('', 'All types');
            setSearch('');
        });
    }

    /* Row actions (Bootstrap handles menu open/close) */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-row-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-row-action');
        if (action === 'copy') {
            var id = btn.getAttribute('data-movement-id') || '';
            var msg = 'Movement #' + id;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(msg).then(function () {
                    window.showToast('Movement ID copied', 'bi-clipboard-check');
                }).catch(function () {
                    window.showToast(msg, 'bi-clipboard');
                });
            } else {
                window.showToast(msg, 'bi-clipboard');
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

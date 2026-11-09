/* =========================================================
   ADMIN REPORTS — client-side search + status filter + pagination
   ========================================================= */
(function () {
    var body = document.getElementById('reportsBody');
    if (!body) return;

    var empty        = document.getElementById('noResults');
    var showing      = document.getElementById('showingLabel');
    var countEl      = document.getElementById('filterCount');
    var pagination   = document.getElementById('pagination');
    var filterSearch = document.getElementById('filterSearch');
    var clearBtn     = document.getElementById('clearFiltersBtn');
    var statusLabel  = document.getElementById('statusFilterLabel');

    var PAGE_SIZE = 12;
    var currentSearch = '';
    var currentStatus = '';
    var currentPage   = 1;

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr.report-row'));

    function filteredRows() {
        return allRows.filter(function (row) {
            if (currentStatus && row.getAttribute('data-status') !== currentStatus) return false;
            if (!currentSearch) return true;
            var haystack = (row.getAttribute('data-search') || '').toLowerCase();
            return haystack.indexOf(currentSearch) !== -1;
        });
    }

    function setSearch(value) {
        currentSearch = String(value || '').trim().toLowerCase();
        currentPage = 1;
        if (filterSearch && filterSearch.value !== value) filterSearch.value = value;
        var tb = document.getElementById('topbarSearch');
        if (tb && tb.value !== value) tb.value = value;
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
        if (countEl) countEl.textContent = total + ' report' + (total === 1 ? '' : 's');
        if (clearBtn) clearBtn.hidden = !(currentSearch || currentStatus);

        if (showing) {
            showing.textContent = total === 0
                ? 'No reports'
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

    /* Search */
    if (filterSearch) filterSearch.addEventListener('input', function () { setSearch(filterSearch.value); });

    /* Topbar search integration */
    document.addEventListener('topbar:search', function (e) {
        if (!e.detail) return;
        setSearch(e.detail.value);
    });

    /* Status filter */
    document.querySelectorAll('[aria-labelledby="statusFilterBtn"] [data-status]').forEach(function (item) {
        item.addEventListener('click', function () {
            currentStatus = (item.getAttribute('data-status') || '').toLowerCase();
            if (statusLabel) statusLabel.textContent = item.textContent.trim();

            document.querySelectorAll('[aria-labelledby="statusFilterBtn"] [data-status]').forEach(function (x) {
                x.classList.toggle('active', x === item);
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
            });
            if (currentStatus !== '') {
                var i = document.createElement('i');
                i.className = 'bi bi-check2 dropdown-check';
                item.appendChild(i);
            }

            currentPage = 1;
            render();
        });
    });

    /* Clear filters */
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            currentStatus = '';
            if (statusLabel) statusLabel.textContent = 'All statuses';
            document.querySelectorAll('[aria-labelledby="statusFilterBtn"] [data-status]').forEach(function (x) {
                x.classList.remove('active');
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
            });
            var allBtn = document.querySelector('[aria-labelledby="statusFilterBtn"] [data-status=""]');
            if (allBtn) {
                allBtn.classList.add('active');
                var i = document.createElement('i');
                i.className = 'bi bi-check2 dropdown-check';
                allBtn.appendChild(i);
            }
            setSearch('');
        });
    }

    /* Row actions */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-action');

        if (action === 'copy-email') {
            var email = btn.getAttribute('data-email') || '';
            if (!email) { window.showToast('No email on file', 'bi-envelope-slash', true); return; }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(email).then(function () {
                    window.showToast('Email copied', 'bi-clipboard-check');
                }).catch(function () {
                    window.showToast(email, 'bi-clipboard');
                });
            } else {
                window.showToast(email, 'bi-clipboard');
            }
        }
    });

    /* ⌘K / Ctrl+K */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && String(e.key).toLowerCase() === 'k') {
            e.preventDefault();
            if (filterSearch) filterSearch.focus();
        }
    });

    render();
})();

/* =========================================================
   ADMIN DASHBOARD — recent users (search + role/status filters + pagination)
   Topbar provides window.showToast and the 'topbar:search' event.
   Loaded by pages/admin/dashboard/index.php
   ========================================================= */
(function () {
    var body = document.getElementById('usersBody');
    if (!body) return;

    var empty        = document.getElementById('noResults');
    var showing      = document.getElementById('showingLabel');
    var countEl      = document.getElementById('filterCount');
    var pagination   = document.getElementById('pagination');
    var filterSearch = document.getElementById('filterSearch');
    var clearBtn     = document.getElementById('clearFiltersBtn');

    var roleLabel    = document.getElementById('roleFilterLabel');
    var statusLabel  = document.getElementById('statusFilterLabel');

    var PAGE_SIZE = 10;
    var currentSearch = '';
    var currentRole   = '';
    var currentStatus = '';
    var currentPage   = 1;

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr'));

    function filteredRows() {
        return allRows.filter(function (row) {
            var roleOk   = !currentRole   || row.getAttribute('data-role')   === currentRole;
            var statusOk = !currentStatus || row.getAttribute('data-status') === currentStatus;
            if (!roleOk || !statusOk) return false;
            if (!currentSearch) return true;
            var haystack = [
                row.getAttribute('data-name')  || '',
                row.getAttribute('data-email') || '',
                row.getAttribute('data-role')  || '',
                row.getAttribute('data-status') || ''
            ].join(' ').toLowerCase();
            return haystack.indexOf(currentSearch) !== -1;
        });
    }

    function syncInputs(value) {
        if (filterSearch && filterSearch.value !== value) filterSearch.value = value;
        var tb = document.getElementById('topbarSearch');
        if (tb && tb.value !== value) tb.value = value; /* no 'input' event is fired */
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
        if (countEl) countEl.textContent = total + (total === 1 ? ' user' : ' users');
        if (clearBtn) clearBtn.hidden = !(currentSearch || currentRole || currentStatus);

        if (showing) {
            showing.textContent = total === 0
                ? 'No users'
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

    /* In-page search */
    if (filterSearch) filterSearch.addEventListener('input', function () { setSearch(filterSearch.value); });

    /* Topbar search — topbar dispatches this event on every keystroke */
    document.addEventListener('topbar:search', function (e) {
        if (!e.detail) return;
        setSearch(e.detail.value);
    });

    /* Role filter — button-click (Bootstrap handles open/close).
       Scoped to the filter menu so row-action clicks never trigger it. */
    document.querySelectorAll('[aria-labelledby="roleFilterBtn"] [data-role]').forEach(function (item) {
        item.addEventListener('click', function () {
            currentRole = item.getAttribute('data-role') || '';
            if (roleLabel) roleLabel.textContent = item.textContent.trim();
            document.querySelectorAll('[aria-labelledby="roleFilterBtn"] [data-role]').forEach(function (x) {
                x.classList.toggle('active', x === item);
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
                if (x === item && currentRole !== '') {
                    var i = document.createElement('i');
                    i.className = 'bi bi-check2 dropdown-check';
                    x.appendChild(i);
                }
            });
            currentPage = 1;
            render();
        });
    });

    /* Status filter */
    document.querySelectorAll('[aria-labelledby="statusFilterBtn"] [data-status]').forEach(function (item) {
        item.addEventListener('click', function () {
            currentStatus = item.getAttribute('data-status') || '';
            if (statusLabel) statusLabel.textContent = item.textContent.trim();
            document.querySelectorAll('[aria-labelledby="statusFilterBtn"] [data-status]').forEach(function (x) {
                x.classList.toggle('active', x === item);
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
                if (x === item && currentStatus !== '') {
                    var i = document.createElement('i');
                    i.className = 'bi bi-check2 dropdown-check';
                    x.appendChild(i);
                }
            });
            currentPage = 1;
            render();
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            currentRole = '';
            currentStatus = '';
            if (roleLabel)   roleLabel.textContent = 'All roles';
            if (statusLabel) statusLabel.textContent = 'All statuses';
            document.querySelectorAll('[aria-labelledby="roleFilterBtn"] [data-role], [aria-labelledby="statusFilterBtn"] [data-status]').forEach(function (x) {
                x.classList.remove('active');
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
            });
            setSearch('');
        });
    }

    /* Row actions */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-row-action]');
        if (!btn) return;
        if (btn.getAttribute('data-row-action') === 'copy-email') {
            var email = btn.getAttribute('data-email') || '';
            if (!email) { window.showToast('No email on file', 'bi-envelope-slash'); return; }
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

    /* ⌘K / Ctrl+K — topbar already focuses #topbarSearch; page then focuses its own filter */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && String(e.key).toLowerCase() === 'k') {
            e.preventDefault();
            if (filterSearch) filterSearch.focus();
        }
    });

    render();
})();

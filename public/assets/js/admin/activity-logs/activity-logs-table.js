/* =========================================================
   ACTIVITY LOGS — client-side search + action filter
   Server pagination stays intact; filter state is mirrored
   to URL (?q=...&action=...) so it survives page navigation.
   ========================================================= */
(function () {
    var body = document.getElementById('logsBody');
    if (!body) return;

    var empty        = document.getElementById('noResults');
    var countEl      = document.getElementById('filterCount');
    var filterSearch = document.getElementById('filterSearch');
    var clearBtn     = document.getElementById('clearFiltersBtn');
    var actionLabel  = document.getElementById('actionFilterLabel');
    var paginationEl = document.getElementById('pagination');

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr.log-row'));

    /* Snapshot original pagination hrefs so we can rebuild with filter params */
    if (paginationEl) {
        paginationEl.querySelectorAll('a').forEach(function (a) {
            if (!a.hasAttribute('data-base-href')) {
                a.setAttribute('data-base-href', a.getAttribute('href') || '');
            }
        });
    }

    /* Read initial state from URL */
    var params = new URLSearchParams(window.location.search);
    var currentSearch = (params.get('q') || '').trim().toLowerCase();
    var currentAction = (params.get('action') || '').trim().toLowerCase();

    /* Reflect into inputs */
    if (filterSearch && currentSearch) filterSearch.value = params.get('q');
    var tbSearch = document.getElementById('topbarSearch');
    if (tbSearch && currentSearch) tbSearch.value = params.get('q');

    /* Reflect action into dropdown */
    if (currentAction) {
        var match = document.querySelector('[aria-labelledby="actionFilterBtn"] [data-action="' + currentAction + '"]');
        if (match && actionLabel) {
            actionLabel.textContent = match.textContent.trim();
            document.querySelectorAll('[aria-labelledby="actionFilterBtn"] [data-action]').forEach(function (x) {
                x.classList.toggle('active', x === match);
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
            });
            var i = document.createElement('i');
            i.className = 'bi bi-check2 dropdown-check';
            match.appendChild(i);
        } else {
            currentAction = '';
        }
    }

    function filteredRows() {
        return allRows.filter(function (row) {
            if (currentAction && row.getAttribute('data-action-group') !== currentAction) return false;
            if (!currentSearch) return true;
            var haystack = (row.getAttribute('data-search') || '').toLowerCase();
            return haystack.indexOf(currentSearch) !== -1;
        });
    }

    function syncPaginationLinks() {
        if (!paginationEl) return;
        paginationEl.querySelectorAll('a[data-base-href]').forEach(function (a) {
            var base = a.getAttribute('data-base-href');
            if (!base) return;
            var u = new URL(base, window.location.origin);
            if (currentSearch) u.searchParams.set('q', params.get('q') || currentSearch);
            else               u.searchParams.delete('q');
            if (currentAction) u.searchParams.set('action', currentAction);
            else               u.searchParams.delete('action');
            a.setAttribute('href', u.pathname + u.search);
        });
    }

    function syncUrl() {
        var u = new URL(window.location.href);
        if (currentSearch) u.searchParams.set('q', filterSearch && filterSearch.value ? filterSearch.value : currentSearch);
        else               u.searchParams.delete('q');
        if (currentAction) u.searchParams.set('action', currentAction);
        else               u.searchParams.delete('action');
        window.history.replaceState({}, '', u.toString());
        syncPaginationLinks();
    }

    function render() {
        var matched = filteredRows();
        allRows.forEach(function (row) { row.style.display = 'none'; });
        matched.forEach(function (row) { row.style.display = ''; });

        var total = matched.length;
        if (empty) empty.style.display = total === 0 ? 'block' : 'none';
        if (countEl) countEl.textContent = total + ' entr' + (total === 1 ? 'y' : 'ies') + ' on this page';
        if (clearBtn) clearBtn.hidden = !(currentSearch || currentAction);
    }

    function setSearch(value) {
        currentSearch = String(value || '').trim().toLowerCase();
        if (filterSearch && filterSearch.value !== value) filterSearch.value = value;
        var tb = document.getElementById('topbarSearch');
        if (tb && tb.value !== value) tb.value = value;
        syncUrl();
        render();
    }

    /* Search */
    if (filterSearch) filterSearch.addEventListener('input', function () { setSearch(filterSearch.value); });

    /* Topbar search integration */
    document.addEventListener('topbar:search', function (e) {
        if (!e.detail) return;
        setSearch(e.detail.value);
    });

    /* Action filter — scoped to the filter menu so row-action buttons
       (View details / Copy log ID / Copy email) never trigger it. */
    document.querySelectorAll('[aria-labelledby="actionFilterBtn"] [data-action]').forEach(function (item) {
        item.addEventListener('click', function () {
            currentAction = (item.getAttribute('data-action') || '').toLowerCase();
            if (actionLabel) actionLabel.textContent = item.textContent.trim();

            document.querySelectorAll('[aria-labelledby="actionFilterBtn"] [data-action]').forEach(function (x) {
                x.classList.toggle('active', x === item);
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
            });
            if (currentAction !== '') {
                var i = document.createElement('i');
                i.className = 'bi bi-check2 dropdown-check';
                item.appendChild(i);
            }
            syncUrl();
            render();
        });
    });

    /* Clear filters */
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            currentAction = '';
            if (actionLabel) actionLabel.textContent = 'All actions';
            document.querySelectorAll('[aria-labelledby="actionFilterBtn"] [data-action]').forEach(function (x) {
                x.classList.remove('active');
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
            });
            var allBtn = document.querySelector('[aria-labelledby="actionFilterBtn"] [data-action=""]');
            if (allBtn) {
                allBtn.classList.add('active');
                var i = document.createElement('i');
                i.className = 'bi bi-check2 dropdown-check';
                allBtn.appendChild(i);
            }
            setSearch('');
        });
    }

    /* Row actions: copy */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action="copy-id"], [data-action="copy-email"]');
        if (!btn) return;
        var action = btn.getAttribute('data-action');
        var value  = action === 'copy-id'
            ? '#' + (btn.getAttribute('data-log-id') || '')
            : (btn.getAttribute('data-email') || '');

        if (!value || value === '#') { window.showToast('Nothing to copy', 'bi-clipboard-x', true); return; }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
                window.showToast(action === 'copy-id' ? 'Log ID copied' : 'Email copied', 'bi-clipboard-check');
            }).catch(function () {
                window.showToast(value, 'bi-clipboard');
            });
        } else {
            window.showToast(value, 'bi-clipboard');
        }
    });

    /* ⌘K / Ctrl+K focuses in-page filter */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && String(e.key).toLowerCase() === 'k') {
            e.preventDefault();
            if (filterSearch) filterSearch.focus();
        }
    });

    /* Export (current page, respects active filters) */
    var exportBtn = document.getElementById('exportCsvBtn');
    if (exportBtn && window.AppExport) {
        window.AppExport.bind(exportBtn, {
            filename: 'activity-logs-page-' + (window.__LOGS_PAGE || 1),
            columns: ['Log ID', 'Action', 'Actor', 'Email', 'Target', 'IP', 'When', 'Details'],
            getRows: function () {
                return filteredRows().map(function (row) {
                    var trigger = row.querySelector('[data-action="view-detail"]');
                    if (!trigger) return null;
                    return [
                        trigger.getAttribute('data-log-id') || '',
                        trigger.getAttribute('data-log-action') || '',
                        trigger.getAttribute('data-log-actor') || '',
                        trigger.getAttribute('data-log-email') || '',
                        trigger.getAttribute('data-log-target') || '',
                        trigger.getAttribute('data-log-ip') || '',
                        trigger.getAttribute('data-log-when') || '',
                        trigger.getAttribute('data-log-details') || ''
                    ];
                }).filter(Boolean);
            }
        });
    }

    /* Initial render */
    render();
    syncPaginationLinks();
})();

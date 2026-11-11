/* =========================================================
   PRODUCTS / INVENTORY — main catalog logic
   (filters, selection/bulk, row actions, modals, photo picker,
    delete, CSV export, ⌘K)
   Loaded by pages/user/products/index.php and inventory/index.php
   Requires products-ui.js (for window.showToast).
   ========================================================= */
(function () {
    var body = document.getElementById('productBody');
    if (!body) return;

    var empty       = document.getElementById('noResults');
    var showing     = document.getElementById('showingLabel');
    var countEl     = document.getElementById('filterCount');
    var selectAll   = document.getElementById('selectAll');
    var bulkBar     = document.getElementById('bulkBar');
    var bulkCount   = document.getElementById('bulkCount');
    var filterSearch  = document.getElementById('filterSearch');
    var categoryLabel = document.getElementById('categoryFilterLabel');
    var statusLabel   = document.getElementById('statusFilterLabel');
    var clearBtn      = document.getElementById('clearFiltersBtn');

    var currentSearch   = (new URLSearchParams(location.search).get('search') || '').trim().toLowerCase();
    var currentCategory = '0';
    var currentStatus   = '';

    /* ---- Filter + search ---- */
    function applyFilters() {
        var visible = 0;
        body.querySelectorAll('tr').forEach(function (row) {
            var catOk = currentCategory === '0' || row.dataset.category === currentCategory;
            var statusOk = !currentStatus || row.dataset.status === currentStatus;
            var searchOk = !currentSearch
                || (row.dataset.name + ' ' + row.dataset.sku).toLowerCase().indexOf(currentSearch) !== -1;
            var show = catOk && statusOk && searchOk;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        empty.style.display = visible || !body.children.length ? 'none' : 'block';
        countEl.textContent = visible + (visible === 1 ? ' product' : ' products');
        clearBtn.hidden = !(currentSearch || currentCategory !== '0' || currentStatus);

        if (visible === 0) {
            showing.textContent = 'No products';
        } else {
            var total = body.querySelectorAll('tr').length;
            showing.textContent = 'Showing 1–' + visible + ' of ' + total + ' products';
        }

        refreshBulkBar();
        refreshSelectAll();
    }

    filterSearch.addEventListener('input', function () {
        currentSearch = filterSearch.value.trim().toLowerCase();
        document.getElementById('productSearch').value = filterSearch.value;
        applyFilters();
    });
    document.getElementById('productSearch').addEventListener('input', function (event) {
        filterSearch.value = event.target.value;
        currentSearch = event.target.value.trim().toLowerCase();
        applyFilters();
    });

    function markFilterSelection(item) {
        var menu = item.closest('.dropdown-menu-app');
        if (!menu) return;
        menu.querySelectorAll('.dropdown-item-app.is-selected').forEach(function (el) {
            el.classList.remove('is-selected');
        });
        item.classList.add('is-selected');
    }

    document.querySelectorAll('#categoryFilterMenu [data-category]').forEach(function (item, index) {
        if (index === 0) item.classList.add('is-selected');
        item.addEventListener('click', function () {
            currentCategory = item.dataset.category;
            categoryLabel.textContent = item.textContent.trim();
            markFilterSelection(item);
            document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            applyFilters();
        });
    });

    document.querySelectorAll('#statusFilterMenu [data-status]').forEach(function (item, index) {
        if (index === 0) item.classList.add('is-selected');
        item.addEventListener('click', function () {
            currentStatus = item.dataset.status;
            statusLabel.textContent = item.textContent.trim();
            markFilterSelection(item);
            document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });
            applyFilters();
        });
    });

    clearBtn.addEventListener('click', function () {
        currentSearch = '';
        currentCategory = '0';
        currentStatus = '';
        filterSearch.value = '';
        document.getElementById('productSearch').value = '';
        categoryLabel.textContent = 'All categories';
        statusLabel.textContent = 'All status';
        document.querySelectorAll('#categoryFilterMenu [data-category]').forEach(function (el, i) {
            el.classList.toggle('is-selected', i === 0);
        });
        document.querySelectorAll('#statusFilterMenu [data-status]').forEach(function (el, i) {
            el.classList.toggle('is-selected', i === 0);
        });
        applyFilters();
    });

    /* ---- Selection ---- */
    function visibleChecks() {
        return Array.prototype.filter.call(body.querySelectorAll('.row-check'), function (c) {
            return c.closest('tr').style.display !== 'none';
        });
    }
    function refreshSelectAll() {
        var checks = visibleChecks();
        var checked = checks.filter(function (c) { return c.checked; });
        selectAll.checked = checks.length > 0 && checked.length === checks.length;
        selectAll.indeterminate = checked.length > 0 && checked.length < checks.length;
    }
    function refreshBulkBar() {
        var checked = body.querySelectorAll('.row-check:checked');
        bulkCount.textContent = checked.length;
        bulkBar.hidden = checked.length === 0;
    }
    selectAll.addEventListener('change', function () {
        visibleChecks().forEach(function (c) { c.checked = selectAll.checked; });
        refreshBulkBar();
    });
    body.addEventListener('change', function (e) {
        if (e.target.classList.contains('row-check')) {
            refreshBulkBar();
            refreshSelectAll();
        }
    });

    /* ---- Row actions (edit / delete) ---- */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-row-action]');
        if (!btn) return;
        var row = btn.closest('tr');
        var action = btn.dataset.rowAction;
        document.querySelectorAll('.dropdown-menu-app.is-open').forEach(function (m) { m.classList.remove('is-open'); });

        if (action === 'edit') openEdit(row);
        else if (action === 'delete') openDelete(row);
        else if (action === 'duplicate') {
            openEdit(row);
            document.getElementById('fieldId').value = '';
            document.getElementById('fieldSku').value = '';
            /* A duplicate is a new record — the photo doesn't carry over;
               the user can pick a fresh one. */
            resetPhotoPicker('');
            titleEl.textContent = 'Duplicate product';
            subEl.textContent = 'Choose a unique SKU to save a new copy.';
        }
    }, true);

    /* ---- Modal: Add / Edit ---- */
    var modalEl = document.getElementById('productModal');
    var modal   = new bootstrap.Modal(modalEl);
    var form    = document.getElementById('productForm');
    var titleEl = document.getElementById('productModalTitle');
    var subEl   = document.getElementById('productModalSubtitle');
    var formError = document.getElementById('productFormError');

    function showFormError(message) {
        formError.textContent = message;
        formError.hidden = !message;
    }
    modalEl.addEventListener('shown.bs.modal', function () { document.getElementById('fieldName').focus(); });
    document.getElementById('createCategoryBtn').addEventListener('click', async function () {
        var button = this;
        var input = document.getElementById('newCategoryName');
        if (!input.value.trim()) { showFormError('Enter a category name.'); input.focus(); return; }
        button.disabled = true;
        try {
            var payload = new URLSearchParams({ name: input.value.trim(), csrf_token: form.elements.csrf_token.value });
            var response = await fetch('/categories', { method: 'POST', body: payload, headers: { Accept: 'application/json' } });
            var result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Category already exists or could not be created.');
            var categories = await (await fetch('/categories')).json();
            var select = document.getElementById('fieldCategory');
            select.replaceChildren(new Option('Select…', ''));
            categories.forEach(function (category) { select.add(new Option(category.name, category.id)); });
            var created = categories.find(function (category) { return category.name === input.value.trim(); });
            if (created) select.value = created.id;
            input.value = '';
            input.closest('details').open = false;
            showFormError('');
        } catch (error) { showFormError(error.message); }
        finally { button.disabled = false; }
    });

    function openAdd() {
        form.reset();
        showFormError('');
        document.getElementById('fieldId').value = '';
        resetPhotoPicker('');
        titleEl.textContent = 'Add product';
        subEl.textContent = 'Create a new product record.';
        modal.show();
    }

    function openEdit(row) {
        showFormError('');
        document.getElementById('fieldId').value        = row.dataset.id;
        document.getElementById('fieldName').value      = row.dataset.name;
        document.getElementById('fieldSku').value       = row.dataset.sku;
        document.getElementById('fieldCategory').value  = row.dataset.category;
        document.getElementById('fieldDescription').value = row.dataset.description || '';
        document.getElementById('fieldPrice').value     = row.dataset.price;
        document.getElementById('fieldQuantity').value  = row.dataset.quantity;
        document.getElementById('fieldThreshold').value = row.dataset.threshold;
        resetPhotoPicker(row.dataset.image || '');
        titleEl.textContent = 'Edit product';
        subEl.textContent = 'Update the product details.';
        modal.show();
    }

    document.getElementById('addProductBtn').addEventListener('click', openAdd);

    /* ---- Photo picker (optional product photo) ---- */
    var imageInput     = document.getElementById('fieldImage');
    var imagePreview   = document.getElementById('fieldImagePreview');
    var imageFallback  = document.getElementById('fieldImageFallback');
    var removeImageBtn = document.getElementById('removeImageBtn');
    var removeImageLabel = document.getElementById('removeImageLabel');
    var removeImageField = document.getElementById('fieldRemoveImage');
    var PHOTO_MAX_BYTES = 2 * 1024 * 1024;
    var PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    function setPhotoState(state) {
        /* state: 'none' | { path: string } | 'removed' */
        if (state === 'none') {
            imagePreview.hidden = true;
            imageFallback.hidden = false;
            removeImageBtn.hidden = true;
            removeImageField.value = '';
            imageInput.value = '';
        } else if (state === 'removed') {
            imagePreview.hidden = true;
            imageFallback.hidden = false;
            removeImageBtn.hidden = false;
            removeImageLabel.textContent = 'Undo';
            removeImageField.value = '1';
        } else {
            imagePreview.src = state.path;
            imagePreview.hidden = false;
            imageFallback.hidden = true;
            removeImageBtn.hidden = false;
            removeImageLabel.textContent = 'Remove photo';
            removeImageField.value = '';
        }
    }

    function resetPhotoPicker(currentPath) {
        removeImageLabel.textContent = 'Remove photo';
        removeImageField.value = '';
        imageInput.value = '';
        if (currentPath) {
            setPhotoState({ path: currentPath });
        } else {
            setPhotoState('none');
        }
    }

    imageInput.addEventListener('change', function () {
        var file = imageInput.files && imageInput.files[0];
        if (!file) return;
        var rowEl = document.getElementById('fieldId').value !== '' ? document.querySelector('tr[data-id="' + document.getElementById('fieldId').value + '"]') : null;
        var currentPath = rowEl && rowEl.dataset.image ? rowEl.dataset.image : '';
        if (PHOTO_TYPES.indexOf(file.type) === -1) {
            showFormError('Only JPEG, PNG or WebP images are supported.');
            resetPhotoPicker(currentPath);
            return;
        }
        if (file.size > PHOTO_MAX_BYTES) {
            showFormError('The image is too large. Maximum size is 2 MB.');
            resetPhotoPicker(currentPath);
            return;
        }
        showFormError('');
        setPhotoState({ path: URL.createObjectURL(file) });
    });

    removeImageBtn.addEventListener('click', function () {
        /* Toggle removal: first click flags it, second click undoes. */
        if (removeImageField.value === '1') {
            /* Undo — restore whatever was there before. */
            var rowEl = document.getElementById('fieldId').value !== '' ? document.querySelector('tr[data-id="' + document.getElementById('fieldId').value + '"]') : null;
            resetPhotoPicker(rowEl && rowEl.dataset.image ? rowEl.dataset.image : '');
        } else {
            imageInput.value = '';
            setPhotoState('removed');
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!form.reportValidity()) return;
        showFormError('');
        var btn = document.getElementById('productSubmitBtn');
        var orig = btn.innerHTML;
        var isEdit = document.getElementById('fieldId').value !== '';

        var payload = new FormData(form);
        var url = isEdit ? '/products/update' : '/products';

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i>Saving…';

        fetch(url, { method: 'POST', body: payload, credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (res) {
                if (res.status === 404) {
                    return { success: false, __missing: true };
                }
                return res.json();
            })
            .then(function (data) {
                if (data.__missing) {
                    window.showToast(
                        isEdit
                            ? 'Edit endpoint missing — add POST /products/update'
                            : 'Create endpoint missing — add POST /products',
                        'bi-info-circle'
                    );
                    btn.disabled = false;
                    btn.innerHTML = orig;
                    return;
                }
                if (data.success) {
                    window.showToast(isEdit ? 'Product updated' : 'Product created', 'bi-check2-circle');
                    modal.hide();
                    setTimeout(function () { window.location.reload(); }, 400);
                } else {
                    showFormError(data.message || 'Could not save product. Check the fields and use a unique SKU.');
                    btn.disabled = false;
                    btn.innerHTML = orig;
                }
            })
            .catch(function () {
                showFormError('Unable to save. Check your connection and try again.');
                btn.disabled = false;
                btn.innerHTML = orig;
            });
    });

    /* ---- Modal: Delete ---- */
    var deleteEl    = document.getElementById('deleteModal');
    var deleteModal = new bootstrap.Modal(deleteEl);
    var deleteName  = document.getElementById('deleteProductName');
    var deleteBtn   = document.getElementById('confirmDeleteBtn');
    var pendingIds  = [];
    var deleteError = document.getElementById('deleteError');

    function openDelete(row) {
        pendingIds = [row.dataset.id];
        deleteError.hidden = true;
        deleteName.textContent = row.dataset.name;
        deleteModal.show();
    }

    deleteBtn.addEventListener('click', async function () {
        if (!pendingIds.length) return;
        var orig = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i>Deleting…';
        deleteError.hidden = true;
        try {
            // Reuse the existing authorized single-delete endpoint, acknowledging each success.
            while (pendingIds.length) {
                var id = pendingIds[0];
                var fd = new URLSearchParams({ id: id, csrf_token: form.elements.csrf_token.value });
                var response = await fetch('/products/delete', { method: 'POST', body: fd, headers: { Accept: 'application/json' } });
                var result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Could not delete product.');
                pendingIds.shift();
                var row = body.querySelector('tr[data-id="' + id + '"]');
                if (row) row.remove();
            }
            window.location.reload();
        } catch (error) {
            deleteError.textContent = error.message + ' Any products already deleted will stay deleted.';
            deleteError.hidden = false;
            applyFilters();
        } finally {
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = orig;
        }
    });

    /* ---- Export (visible rows / selected rows) ----
       CSV + Excel logic lives in the shared /assets/js/export.js module. */
    var exportColumns = [
        'Name',
        'SKU',
        'Category',
        { label: 'Price', type: 'number' },
        { label: 'Quantity', type: 'number' },
        { label: 'Threshold', type: 'number' },
        'Status'
    ];

    function exportRowValues(row) {
        return [
            row.dataset.name,
            row.dataset.sku,
            row.dataset.categoryName,
            row.dataset.price,
            row.dataset.quantity,
            row.dataset.threshold,
            row.dataset.status
        ];
    }

    if (window.AppExport) {
        window.AppExport.bind(document.getElementById('exportBtn'), {
            filename: 'products',
            columns: exportColumns,
            getRows: function () {
                return Array.from(body.querySelectorAll('tr')).filter(function (row) {
                    return row.style.display !== 'none';
                }).map(exportRowValues);
            }
        });

        window.AppExport.bind(document.getElementById('bulkExportBtn'), {
            filename: 'products-selected',
            columns: exportColumns,
            getRows: function () {
                return Array.from(body.querySelectorAll('.row-check:checked')).map(function (input) {
                    return input.closest('tr');
                }).filter(Boolean).map(exportRowValues);
            }
        });
    }

    /* ---- Bulk actions ---- */
    document.getElementById('bulkDeleteBtn').addEventListener('click', function () {
        var checked = body.querySelectorAll('.row-check:checked');
        if (!checked.length) return;
        pendingIds = Array.from(checked).map(function (input) { return input.closest('tr').dataset.id; });
        deleteName.textContent = pendingIds.length + ' selected products';
        deleteError.hidden = true;
        deleteModal.show();
    });

    /* ---- ⌘K focuses search ---- */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            filterSearch.focus();
        }
    });

    /* ---- Init ---- */
    filterSearch.value = currentSearch;
    document.getElementById('productSearch').value = currentSearch;
    applyFilters();
})();

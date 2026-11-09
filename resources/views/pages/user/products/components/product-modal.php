<!-- ==========================================================
     COMPONENT: Add / Edit product modal (shared)
     ========================================================== -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <form id="productForm" novalidate enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="id" id="fieldId" value="">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="productModalTitle">Add product</h2>
                        <p class="modal-subtitle-app" id="productModalSubtitle">Create a new product record.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <p id="productFormError" class="form-hint" role="alert" style="color:var(--danger)" hidden></p>
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldName">Product name</label>
                            <input class="form-control-app" type="text" id="fieldName" name="name" required placeholder="e.g. Wireless Mouse">
                        </div>
                    </div>

                    <div class="form-row two-col">
                        <div>
                            <label class="form-label-app" for="fieldSku">SKU</label>
                            <input class="form-control-app" type="text" id="fieldSku" name="sku" required placeholder="WM-0000">
                        </div>
                        <div>
                            <label class="form-label-app" for="fieldCategory">Category</label>
                            <select class="form-select-app" id="fieldCategory" name="category_id" required>
                                <option value="">Select…</option>
                                <?php foreach ($categories as $c):
                                    $cid = (int) ($c['id'] ?? $c['category_id'] ?? 0);
                                    $cname = (string) ($c['name'] ?? '');
                                    if (!$cid) continue;
                                ?>
                                    <option value="<?= $cid ?>"><?= $e($cname) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <details class="form-hint">
                                <summary>Add a category</summary>
                                <label class="form-label-app" for="newCategoryName">Category name</label>
                                <input class="form-control-app" id="newCategoryName" maxlength="100" placeholder="e.g. Accessories">
                                <button type="button" class="btn-secondary" id="createCategoryBtn">Create category</button>
                            </details>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldDescription">Description <span style="font-weight:400;color:var(--text-tertiary)">(optional)</span></label>
                            <textarea class="form-control-app" id="fieldDescription" name="description" rows="2" placeholder="Short description of the product" style="height:auto;padding:10px 12px;resize:vertical"></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldImage">Photo <span style="font-weight:400;color:var(--text-tertiary)">(optional)</span></label>
                            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                                <img id="fieldImagePreview" class="product-avatar product-avatar--img" src="" alt=""
                                     style="width:52px;height:52px;border-radius:10px" hidden>
                                <span class="product-avatar" id="fieldImageFallback" aria-hidden="true"
                                      style="width:52px;height:52px;border-radius:10px;font-size:18px">?</span>
                                <div style="display:flex;flex-direction:column;gap:8px;min-width:200px;flex:1">
                                    <input class="form-control-app" type="file" id="fieldImage" name="image"
                                           accept="image/jpeg,image/png,image/webp" style="height:auto;padding:8px 10px">
                                    <button type="button" class="btn-secondary" id="removeImageBtn" hidden>
                                        <i class="bi bi-trash3" aria-hidden="true"></i><span id="removeImageLabel">Remove photo</span>
                                    </button>
                                </div>
                                <input type="hidden" name="remove_image" id="fieldRemoveImage" value="">
                            </div>
                            <p class="form-hint">JPEG, PNG or WebP — up to 2 MB.</p>
                        </div>
                    </div>

                    <div class="form-row two-col">
                        <div>
                            <label class="form-label-app" for="fieldPrice">Price (₱)</label>
                            <input class="form-control-app" type="number" id="fieldPrice" name="price" min="0" step="0.01" required placeholder="0.00">
                        </div>
                        <div>
                            <label class="form-label-app" for="fieldQuantity">Quantity</label>
                            <input class="form-control-app" type="number" id="fieldQuantity" name="quantity" min="0" required placeholder="0">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="fieldThreshold">Low stock threshold</label>
                            <input class="form-control-app" type="number" id="fieldThreshold" name="low_stock_threshold" min="0" value="10" placeholder="10">
                            <p class="form-hint">Alert when quantity drops to or below this number.</p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app" id="productSubmitBtn">
                        <i class="bi bi-check2" aria-hidden="true"></i>Save product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

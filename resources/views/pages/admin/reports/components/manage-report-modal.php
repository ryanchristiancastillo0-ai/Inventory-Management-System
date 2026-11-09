<!-- ==========================================================
     MODAL: Manage / update report
     ========================================================== -->
<div class="modal fade" id="manageReportModal" tabindex="-1" aria-labelledby="manageReportTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-app">
            <form method="post" action="/admin/reports/update">
                <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="id" id="mr-id" value="">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="manageReportTitle">Manage report</h2>
                        <p class="modal-subtitle-app">Update status and respond to the reporter.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <!-- Report preview -->
                    <div class="report-preview">
                        <p class="report-preview-title" id="mr-subject">—</p>
                        <p class="report-preview-meta">
                            <span id="mr-reporter">—</span>
                            · <span id="mr-when">—</span>
                        </p>
                        <p class="report-preview-body" id="mr-desc"></p>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="mr-status">Status</label>
                            <select class="form-select-app" id="mr-status" name="status" required>
                                <option value="PENDING">Pending</option>
                                <option value="IN_PROGRESS">In progress</option>
                                <option value="RESOLVED">Resolved</option>
                                <option value="REJECTED">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="mr-response">Admin response</label>
                            <textarea class="form-control-app"
                                      id="mr-response"
                                      name="admin_response"
                                      rows="5"
                                      maxlength="2000"
                                      placeholder="Explain what was done, or why the report was dismissed…"></textarea>
                            <p style="font-size:11px;color:var(--text-tertiary);margin:6px 0 0">
                                The reporter will see this response.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app">
                        <i class="bi bi-check2" aria-hidden="true"></i>Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

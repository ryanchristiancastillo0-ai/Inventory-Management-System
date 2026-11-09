<!-- ==========================================================
     MODAL: Activate / Deactivate user
    Account deletion is disabled; the admin manages accounts
     by activating and deactivating them instead.
     ========================================================== -->
<div class="modal fade" id="userStatusModal" tabindex="-1" aria-labelledby="userStatusTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-content-app">
            <form method="post" action="/admin/users/status">
                <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="id" id="status-id" value="">
                <input type="hidden" name="is_active" id="status-is-active" value="">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="userStatusTitle">Deactivate user</h2>
                        <p class="modal-subtitle-app" id="userStatusSubtitle">They will no longer be able to log in.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <p style="margin:0;font-size:13px;color:var(--text)">
                        <span id="status-verb">Deactivate</span>
                        <strong id="status-username">this user</strong>'s account?
                    </p>
                    <p style="margin:8px 0 0;font-size:12px;color:var(--text-tertiary)" id="status-note">
                        The account and its data stay intact and can be re-activated at any time.
                    </p>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app" id="status-submit" style="background:var(--danger)">
                        <i class="bi bi-pause-circle" aria-hidden="true"></i><span id="status-submit-label">Deactivate</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

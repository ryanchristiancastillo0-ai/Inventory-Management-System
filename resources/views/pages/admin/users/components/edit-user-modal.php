<!-- ==========================================================
     MODAL: Edit user
    Username and email are view-only for the admin — identity
     fields cannot be changed here, only the role and password.
     ========================================================== -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <form method="post" action="/admin/users/update" autocomplete="off">
                <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="id" id="edit-id" value="">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="editUserTitle">Edit user</h2>
                        <p class="modal-subtitle-app">Update the role or reset the password.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="edit-username">Username</label>
                            <input class="form-control-app" type="text" id="edit-username"
                                   readonly aria-readonly="true">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="edit-email">Email</label>
                            <input class="form-control-app" type="email" id="edit-email"
                                   readonly aria-readonly="true">
                        </div>
                    </div>
                    <div class="form-row two-col">
                        <div>
                            <label class="form-label-app" for="edit-password">New password (optional)</label>
                            <input class="form-control-app" type="password" id="edit-password" name="password"
                                   minlength="8" placeholder="Leave blank to keep">
                        </div>
                        <div>
                            <label class="form-label-app" for="edit-role">Role</label>
                            <select class="form-select-app" id="edit-role" name="role" required>
                                <option value="USER">User</option>
                                <option value="ADMIN">Admin</option>
                            </select>
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

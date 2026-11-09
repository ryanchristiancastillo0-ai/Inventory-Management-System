<!-- ==========================================================
     MODAL: New user
     ========================================================== -->
<div class="modal fade" id="newUserModal" tabindex="-1" aria-labelledby="newUserTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-app">
            <form method="post" action="/admin/users/create" autocomplete="off">
                <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="newUserTitle">Add new user</h2>
                        <p class="modal-subtitle-app">Create an admin or standard user account.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="new-username">Username</label>
                            <input class="form-control-app" type="text" id="new-username" name="username"
                                   required minlength="3" maxlength="60" placeholder="e.g. jdoe">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="new-email">Email</label>
                            <input class="form-control-app" type="email" id="new-email" name="email"
                                   required maxlength="255" placeholder="name@example.com">
                        </div>
                    </div>
                    <div class="form-row two-col">
                        <div>
                            <label class="form-label-app" for="new-password">Password</label>
                            <input class="form-control-app" type="password" id="new-password" name="password"
                                   required minlength="8" placeholder="Min. 8 characters">
                        </div>
                        <div>
                            <label class="form-label-app" for="new-role">Role</label>
                            <select class="form-select-app" id="new-role" name="role" required>
                                <option value="USER" selected>User</option>
                                <option value="ADMIN">Admin</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app">
                        <i class="bi bi-check2" aria-hidden="true"></i>Create user
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

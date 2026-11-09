<!-- ==========================================================
     Users table
     ========================================================== -->
<section class="section-gap">
    <div class="panel">

        <?php if (empty($users)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                <h3 class="empty-title">No users yet</h3>
                <p class="empty-text">Add your first admin or team member to get started.</p>
                <button type="button" class="btn-primary-app" data-bs-toggle="modal" data-bs-target="#newUserModal">
                    <i class="bi bi-person-plus" aria-hidden="true"></i>Add first user
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table activity-table align-middle">
                    <caption>Users: name, email, role, status and created date.</caption>
                    <thead>
                        <tr>
                            <th scope="col">User</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Created</th>
                            <th scope="col" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody id="usersBody">
                        <?php foreach ($users as $u):
                            $uid     = (int) ($u['id'] ?? 0);
                            $uname   = $pickStr($u, ['username', 'name', 'full_name', 'fullName'], 'User #' . $uid);
                            $uemail  = $pickStr($u, ['email', 'email_address', 'mail'], '');
                            $urole   = $pickStr($u, ['role', 'role_name', 'user_role'], 'USER');
                            $ucreated = $pickStr($u, ['created_at', 'created_on', 'joined_at', 'registered_at', 'date_joined'], '');

                            $rKey   = $roleKey($urole);
                            $rLabel = $roleLabel($urole);
                            $initial = strtoupper(substr($uname !== '' ? $uname : '?', 0, 1));

                            $uactive  = (int) ($u['is_active'] ?? 1) === 1;
                        ?>
                            <tr class="user-row"
                                data-user-id="<?= $uid ?>"
                                data-username="<?= $e($uname) ?>"
                                data-email="<?= $e($uemail) ?>"
                                data-role="<?= $e($rKey) ?>"
                                data-is-active="<?= $uactive ? '1' : '0' ?>">
                                <td>
                                    <div class="user-cell">
                                        <span class="user-avatar" aria-hidden="true"><?= $e($initial) ?></span>
                                        <div class="user-meta">
                                            <p class="user-name"><?= $e($uname) ?></p>
                                            <?php if ($uemail !== ''): ?>
                                                <p class="user-email"><?= $e($uemail) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge is-<?= $e($rKey) ?>">
                                        <i class="bi <?= $rKey === 'admin' ? 'bi-shield-lock' : 'bi-person' ?>" aria-hidden="true"></i>
                                        <?= $e($rLabel) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $uactive ? 'is-active' : 'is-inactive' ?>">
                                        <i class="bi <?= $uactive ? 'bi-check2-circle' : 'bi-dash-circle' ?>" aria-hidden="true"></i>
                                        <?= $uactive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="cell-when">
                                    <?= $e($fmtWhen($ucreated)) ?>
                                    <?php if ($ucreated !== ''): ?>
                                        <div class="rel"><?= $e($fmtRelative($ucreated)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-actions">
                                    <!-- Row actions — Bootstrap dropdown -->
                                    <div class="dropdown">
                                        <button type="button" class="icon-btn row-actions-btn"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                                aria-expanded="false"
                                                aria-label="Row actions">
                                            <i class="bi bi-three-dots" aria-hidden="true"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item" data-action="edit"
                                                        data-user-id="<?= $uid ?>"
                                                        data-username="<?= $e($uname) ?>"
                                                        data-email="<?= $e($uemail) ?>"
                                                        data-role="<?= $e($rKey) ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editUserModal">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>Edit user
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" data-action="copy-email"
                                                        data-email="<?= $e($uemail) ?>">
                                                    <i class="bi bi-clipboard" aria-hidden="true"></i>Copy email
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <?php if ($uactive): ?>
                                                    <button type="button" class="dropdown-item is-danger" data-bs-toggle="modal"
                                                            data-bs-target="#userStatusModal"
                                                            data-user-id="<?= $uid ?>"
                                                            data-username="<?= $e($uname) ?>"
                                                            data-next-status="0">
                                                        <i class="bi bi-pause-circle" aria-hidden="true"></i>Deactivate user
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                                            data-bs-target="#userStatusModal"
                                                            data-user-id="<?= $uid ?>"
                                                            data-username="<?= $e($uname) ?>"
                                                            data-next-status="1">
                                                        <i class="bi bi-play-circle" aria-hidden="true"></i>Activate user
                                                    </button>
                                                <?php endif; ?>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="no-results" id="noResults" role="status">No users match your filters.</div>

            <div class="table-footer">
                <span id="showingLabel">
                    Showing 1–<?= number_format(count($users)) ?> of <?= number_format(count($users)) ?>
                </span>
                <div class="pagination" id="pagination" aria-label="Pagination"></div>
            </div>
        <?php endif; ?>

    </div>
</section>

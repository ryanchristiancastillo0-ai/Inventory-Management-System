<!-- ==========================================================
     COMPONENT: Recent users table
     ========================================================== -->
<section class="section-gap">
    <div class="panel">

        <?php if (empty($recentUsers)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                <h3 class="empty-title">No users yet</h3>
                <p class="empty-text">Newly registered users will appear here as they sign up.</p>
                <a href="/admin/users" class="btn-primary-app">
                    <i class="bi bi-person-plus" aria-hidden="true"></i>Add first user
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table activity-table align-middle">
                    <caption>Recent users: name, role, status and registration date.</caption>
                    <thead>
                        <tr>
                            <th scope="col">User</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Registered</th>
                            <th scope="col" style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody id="usersBody">
                        <?php foreach ($recentUsers as $u):
                            $uid     = (int) ($u['id'] ?? 0);
                            $uname   = $pickStr($u, ['name', 'full_name', 'fullName', 'username'], 'User #' . $uid);
                            $uemail  = $pickStr($u, ['email', 'email_address', 'mail'], '');
                            $urole   = $pickStr($u, ['role', 'role_name', 'type', 'user_role'], 'user');
                            $ustatus = $pickStr($u, ['status', 'state', 'account_status'], 'active');
                            $ujoin   = $pickStr($u, ['created_at', 'created_on', 'joined_at', 'registered_at', 'date_joined'], '');

                            $initial  = strtoupper(substr($uname !== '' ? $uname : '?', 0, 1));
                            $roleCls  = $userRoleCls($urole);
                            $statCls  = $userStatusCls($ustatus);
                        ?>
                            <tr class="user-row"
                                data-user-id="<?= $uid ?>"
                                data-role="<?= $e(strtolower($urole)) ?>"
                                data-status="<?= $e(strtolower($ustatus)) ?>"
                                data-name="<?= $e($uname) ?>"
                                data-email="<?= $e($uemail) ?>"
                            >
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
                                    <span class="role-badge <?= $e($roleCls) ?>">
                                        <i class="bi bi-circle-fill" aria-hidden="true"></i>
                                        <?= $e($urole) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status <?= $e($statCls) ?>"><?= $e($ustatus) ?></span>
                                </td>
                                <td class="cell-when">
                                    <?= $e($fmtWhen($ujoin)) ?>
                                    <?php if ($ujoin !== ''): ?>
                                        <div class="rel"><?= $e($fmtRelative($ujoin)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-actions">
                                    <div class="dropdown">
                                        <button type="button" class="icon-btn row-actions-btn" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Row actions">
                                            <i class="bi bi-three-dots" aria-hidden="true"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="/admin/users">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>View user
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="/admin/users">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>Edit user
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" class="dropdown-item" data-row-action="copy-email" data-email="<?= $e($uemail) ?>">
                                                    <i class="bi bi-clipboard" aria-hidden="true"></i>Copy email
                                                </button>
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
                    Showing 1–<?= number_format(count($recentUsers)) ?> of <?= number_format(count($recentUsers)) ?>
                </span>
                <div class="pagination" id="pagination" aria-label="Pagination"></div>
            </div>
        <?php endif; ?>

    </div>
</section>

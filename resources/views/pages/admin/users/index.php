<?php
/**
 * ADMIN USER MANAGEMENT PAGE
 * Rendered by App\Core\View with layout "admin" (no topbar/sidebar here —
 * those come from app.php).
 *
 * @var array       $users       List of user records
 * @var array       $stats       KPI map (auto-detected keys)
 * @var array|null  $flash       ['type' => 'success'|'danger', 'message' => string]
 * @var string      $csrfToken
 */

$users     = $users     ?? [];
$stats     = $stats     ?? [];
$flash     = $flash     ?? null;
$csrfToken = $csrfToken ?? '';

/* ---------- Safe helpers ---------- */
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$pickStr = static function (array $rec, array $keys, string $default = '') {
    foreach ($keys as $k) {
        if (isset($rec[$k]) && $rec[$k] !== '' && $rec[$k] !== null) return (string) $rec[$k];
    }
    return $default;
};

$pickNum = static function (array $rec, array $keys, $default = 0) {
    foreach ($keys as $k) {
        if (isset($rec[$k]) && is_numeric($rec[$k])) return (int) $rec[$k];
    }
    return (int) $default;
};

/* ---------- KPI extraction (defensive) ---------- */
$totalUsers   = $pickNum($stats, ['total', 'total_users', 'users_count', 'totalUsers'], 0);
$adminCount   = $pickNum($stats, ['admins', 'admin_count', 'adminCount', 'total_admins'], 0);
$regularCount = $pickNum($stats, ['regular', 'users', 'user_count', 'regular_users'], 0);
$newThisMonth = $pickNum($stats, ['new', 'new_users', 'recent', 'this_month', 'new_this_month'], 0);

/* Compute from $users if stats came back empty */
if ($totalUsers === 0 && !empty($users)) {
    $totalUsers   = count($users);
    $adminCount   = 0;
    $regularCount = 0;
    $newThisMonth = 0;
    $nowMonth     = date('Y-m');
    foreach ($users as $u) {
        $r = strtoupper((string) ($u['role'] ?? ''));
        if ($r === 'ADMIN') $adminCount++; else $regularCount++;
        $created = (string) ($u['created_at'] ?? $u['created_on'] ?? '');
        if ($created !== '' && strpos($created, $nowMonth) === 0) $newThisMonth++;
    }
}

/* ---------- Formatting helpers ---------- */
$fmtWhen = static function (string $ts) {
    if ($ts === '') return '—';
    $t = strtotime($ts);
    return $t === false ? $ts : date('M j, Y', $t);
};

$fmtRelative = static function (string $ts) {
    if ($ts === '') return '';
    $t = strtotime($ts);
    if ($t === false) return '';
    $diff = time() - $t;
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $t);
};

$roleKey = static function (string $role): string {
    $r = strtoupper(trim($role));
    return $r === 'ADMIN' ? 'admin' : 'user';
};

$roleLabel = static function (string $role): string {
    $r = strtoupper(trim($role));
    if ($r === 'ADMIN') return 'Admin';
    if ($r === 'USER')  return 'User';
    return $r === '' ? 'User' : ucfirst(strtolower($r));
};

/* ---------- Flash ---------- */
$flashType = (string) ($flash['type'] ?? '');
$flashMsg  = (string) ($flash['message'] ?? '');
?>

<style>
    /* ==========================================================
       ADMIN USERS — page-specific styles
       (base tokens/classes come from main.css)
       ========================================================== */

    /* ---------- Page header ---------- */
    .page-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        min-width: 0;
    }
    .page-header > * { min-width: 0; }
    .page-header .page-title { margin: 0; }
    .page-header .page-subtitle {
        margin: 6px 0 0;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .date-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--info-bg);
        color: var(--info);
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }
    .date-chip i { font-size: 11px; }

    /* ---------- Stats strip (4 KPIs) ---------- */
    .stats-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-top: 24px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .stat-item {
        padding: 20px 22px;
        border-right: 1px solid var(--border);
        min-width: 0;
        position: relative;
        transition: background-color 140ms ease;
    }
    .stat-item:last-child { border-right: 0; }
    .stat-item:hover { background: var(--surface-muted); }

    .stat-top {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        min-width: 0;
    }
    .stat-dot {
        width: 6px; height: 6px; border-radius: 50%;
        background: var(--text-tertiary);
        flex-shrink: 0;
    }
    .stat-dot.is-users { background: var(--info); }
    .stat-dot.is-admin { background: var(--text); }
    .stat-dot.is-user  { background: var(--text-tertiary); }
    .stat-dot.is-new   { background: var(--success); }

    .stat-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-secondary);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
    }
    .stat-value {
        font-size: 24px;
        font-weight: 600;
        letter-spacing: -0.6px;
        line-height: 1.15;
        margin: 0;
        color: var(--text);
        font-variant-numeric: tabular-nums;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .stat-hint {
        margin: 6px 0 0;
        font-size: 11px;
        color: var(--text-tertiary);
        font-variant-numeric: tabular-nums;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .stat-hint.is-up { color: var(--success); }

    @media (max-width: 991.98px) {
        .stats-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .stat-item:nth-child(2n) { border-right: 0; }
        .stat-item:nth-child(n+3) { border-top: 1px solid var(--border); }
    }

    /* ---------- Filter bar (bulletproof) ---------- */
    .filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 24px;
        min-width: 0;
    }
    .filter-bar__group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        min-width: 0;
    }
    .filter-search {
        width: 300px;
        max-width: 100%;
        flex: 0 1 300px;
        min-width: 0;
    }
    .filter-count {
        font-size: 12px;
        color: var(--text-tertiary);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    @media (max-width: 767.98px) {
        .filter-search { flex: 1 1 100%; width: 100%; }
    }

    /* Bootstrap dropdown theme overrides (page-scoped) */
    #roleFilterBtn { min-width: 160px; justify-content: space-between; }
    @media (max-width: 575.98px) {
        #roleFilterBtn { flex: 1 1 auto; min-width: 0; }
    }

    /* ---------- Users table ---------- */
    .user-row { transition: background-color 120ms ease; }
    .user-row:hover { background: var(--surface-muted); }

    .user-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .user-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: var(--surface-hover);
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        flex-shrink: 0;
        border: 1px solid var(--border);
    }
    .user-meta { min-width: 0; flex: 1; }
    .user-name {
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .user-email {
        font-size: 11px;
        color: var(--text-tertiary);
        margin: 1px 0 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
        white-space: nowrap;
    }
    .role-badge i { font-size: 10px; }
    .role-badge.is-admin { background: var(--info-bg);    color: var(--info); }
    .role-badge.is-user  { background: var(--surface-muted); color: var(--text-secondary); }

    .cell-when {
        font-size: 12px;
        color: var(--text-secondary);
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .cell-when .rel { color: var(--text-tertiary); font-size: 11px; }

    .cell-actions { width: 44px; text-align: right; }
    .row-actions-btn { width: 28px; height: 28px; font-size: 14px; }

    /* ---------- Empty state ---------- */
    .empty-state { padding: 60px 24px; text-align: center; }
    .empty-icon {
        width: 64px; height: 64px;
        border-radius: 16px;
        background: var(--surface-hover);
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        margin-bottom: 18px;
    }
    .empty-title { font-size: 16px; font-weight: 600; color: var(--text); margin: 0 0 6px; }
    .empty-text { font-size: 13px; color: var(--text-secondary); margin: 0 0 22px; }

    /* ---------- Toast (page-owned, self-contained) ---------- */
    .toast-stack {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 2000;
        display: flex;
        flex-direction: column;
        gap: 8px;
        pointer-events: none;
    }
    .toast-app {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: var(--accent);
        color: #fff;
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 500;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        pointer-events: auto;
        animation: toastIn 200ms ease both;
        max-width: 340px;
    }
    .toast-app.is-error { background: var(--danger); }
    .toast-app i { font-size: 14px; flex-shrink: 0; }
    .toast-app.toast-out { animation: toastOut 200ms ease both; }
    @keyframes toastIn  { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes toastOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(8px); } }
</style>


<main class="dashboard-content">

    <!-- ==========================================================
         Page header
         ========================================================== -->
    <section class="page-header" aria-labelledby="pageTitle">
        <div class="min-w-0">
            <h1 class="page-title" id="pageTitle">User management</h1>
            <p class="page-subtitle">
                Manage admins and regular users across your workspace
                <span class="date-chip">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <?= date('l, F j, Y') ?>
                </span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn-primary-app" data-bs-toggle="modal" data-bs-target="#newUserModal">
                <i class="bi bi-person-plus" aria-hidden="true"></i>Add user
            </button>
        </div>
    </section>

    <!-- ==========================================================
         Stats strip (4 KPIs)
         ========================================================== -->
    <section class="stats-strip" aria-label="User statistics">
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-users"></span>
                <p class="stat-label">Total users</p>
            </div>
            <p class="stat-value"><?= number_format($totalUsers) ?></p>
            <p class="stat-hint">Across all roles</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-admin"></span>
                <p class="stat-label">Admins</p>
            </div>
            <p class="stat-value"><?= number_format($adminCount) ?></p>
            <p class="stat-hint">Full access</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-user"></span>
                <p class="stat-label">Regular users</p>
            </div>
            <p class="stat-value"><?= number_format($regularCount) ?></p>
            <p class="stat-hint">Standard access</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-new"></span>
                <p class="stat-label">New this month</p>
            </div>
            <p class="stat-value"><?= number_format($newThisMonth) ?></p>
            <p class="stat-hint <?= $newThisMonth > 0 ? 'is-up' : '' ?>">
                <?= $newThisMonth > 0 ? '+' . number_format($newThisMonth) . ' this period' : 'No new sign-ups' ?>
            </p>
        </div>
    </section>

    <!-- ==========================================================
         Filter bar
         ========================================================== -->
    <section class="filter-bar" aria-label="Filter users">
        <div class="filter-bar__group">
            <div class="search-wrap filter-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search"
                       id="filterSearch"
                       class="search-input"
                       placeholder="Filter by username, email or role..."
                       aria-label="Filter users"
                       autocomplete="off">
            </div>

            <!-- Role filter — Bootstrap dropdown -->
            <div class="dropdown">
                <button type="button" class="btn-secondary" id="roleFilterBtn"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-inline-flex align-items-center gap-2">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                        <span id="roleFilterLabel">All roles</span>
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="roleFilterBtn">
                    <li><div class="dropdown-label-app">Role</div></li>
                    <li>
                        <button type="button" class="dropdown-item active" data-role="">
                            <i class="bi bi-list-ul" aria-hidden="true"></i>All roles
                            <i class="bi bi-check2 dropdown-check" aria-hidden="true"></i>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button type="button" class="dropdown-item" data-role="admin">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>Admin
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item" data-role="user">
                            <i class="bi bi-person" aria-hidden="true"></i>User
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div class="filter-bar__group">
            <span class="filter-count" id="filterCount"><?= number_format(count($users)) ?> user<?= count($users) === 1 ? '' : 's' ?></span>
            <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
                <i class="bi bi-x" aria-hidden="true"></i>Clear
            </button>
        </div>
    </section>

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
                        <caption>Users: name, email, role and created date.</caption>
                        <thead>
                            <tr>
                                <th scope="col">User</th>
                                <th scope="col">Role</th>
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
                            ?>
                                <tr class="user-row"
                                    data-user-id="<?= $uid ?>"
                                    data-username="<?= $e($uname) ?>"
                                    data-email="<?= $e($uemail) ?>"
                                    data-role="<?= $e($rKey) ?>">
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
                                                    <button type="button" class="dropdown-item is-danger" data-action="delete"
                                                            data-user-id="<?= $uid ?>"
                                                            data-username="<?= $e($uname) ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteUserModal">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>Delete user
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
                        Showing 1–<?= number_format(count($users)) ?> of <?= number_format(count($users)) ?>
                    </span>
                    <div class="pagination" id="pagination" aria-label="Pagination"></div>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>


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


<!-- ==========================================================
     MODAL: Edit user
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
                        <p class="modal-subtitle-app">Update account details or reset the password.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="edit-username">Username</label>
                            <input class="form-control-app" type="text" id="edit-username" name="username"
                                   required minlength="3" maxlength="60">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label-app" for="edit-email">Email</label>
                            <input class="form-control-app" type="email" id="edit-email" name="email"
                                   required maxlength="255">
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


<!-- ==========================================================
     MODAL: Delete user
     ========================================================== -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-content-app">
            <form method="post" action="/admin/users/delete">
                <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="id" id="delete-id" value="">

                <div class="modal-header-app">
                    <div>
                        <h2 class="modal-title-app" id="deleteUserTitle">Delete user</h2>
                        <p class="modal-subtitle-app">This action cannot be undone.</p>
                    </div>
                    <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body-app">
                    <p style="margin:0;font-size:13px;color:var(--text)">
                        Permanently delete
                        <strong id="delete-username">this user</strong>?
                    </p>
                    <p style="margin:8px 0 0;font-size:12px;color:var(--text-tertiary)">
                        All their sessions and account data will be removed. You cannot undo this.
                    </p>
                </div>

                <div class="modal-footer-app">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-app" style="background:var(--danger)">
                        <i class="bi bi-trash" aria-hidden="true"></i>Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ==========================================================
     Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>


<script>
/* =========================================================
   TOAST
   ========================================================= */
(function () {
    var stack = document.getElementById('toastStack');

    window.showToast = function (message, icon, isError) {
        if (!stack) return;
        var t = document.createElement('div');
        t.className = 'toast-app' + (isError ? ' is-error' : '');
        t.innerHTML = '<i class="bi ' + (icon || (isError ? 'bi-exclamation-circle' : 'bi-check2-circle')) + '" aria-hidden="true"></i><span></span>';
        t.querySelector('span').textContent = message;
        stack.appendChild(t);
        setTimeout(function () {
            t.classList.add('toast-out');
            t.addEventListener('animationend', function () { t.remove(); }, { once: true });
        }, 3600);
    };
})();

/* =========================================================
   FLASH MESSAGE
   ========================================================= */
<?php if ($flashMsg !== ''): ?>
    document.addEventListener('DOMContentLoaded', function () {
        window.showToast(
            <?= json_encode($flashMsg) ?>,
            <?= json_encode($flashType === 'danger' ? 'bi-exclamation-circle' : 'bi-check2-circle') ?>,
            <?= $flashType === 'danger' ? 'true' : 'false' ?>
        );
    });
<?php endif; ?>

/* =========================================================
   EDIT MODAL — populate fields from the clicked button
   ========================================================= */
(function () {
    var modal = document.getElementById('editUserModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;

        var id       = btn.getAttribute('data-user-id') || '';
        var username = btn.getAttribute('data-username') || '';
        var email    = btn.getAttribute('data-email') || '';
        var role     = btn.getAttribute('data-role') || 'user';

        document.getElementById('edit-id').value       = id;
        document.getElementById('edit-username').value = username;
        document.getElementById('edit-email').value    = email;
        document.getElementById('edit-password').value = '';
        document.getElementById('edit-role').value     = (role === 'admin') ? 'ADMIN' : 'USER';
    });
})();

/* =========================================================
   DELETE MODAL — populate fields from the clicked button
   ========================================================= */
(function () {
    var modal = document.getElementById('deleteUserModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;

        document.getElementById('delete-id').value = btn.getAttribute('data-user-id') || '';
        document.getElementById('delete-username').textContent =
            btn.getAttribute('data-username') || 'this user';
    });
})();

/* =========================================================
   USERS TABLE — search + role filter + pagination
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

    var PAGE_SIZE = 10;
    var currentSearch = '';
    var currentRole   = '';
    var currentPage   = 1;

    // Read initial URL parameters
    var urlParams = new URLSearchParams(window.location.search);
    currentSearch = (urlParams.get('search') || '').trim().toLowerCase();
    currentRole = urlParams.get('role') || '';

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr'));

    function filteredRows() {
        return allRows.filter(function (row) {
            if (currentRole && row.getAttribute('data-role') !== currentRole) return false;
            if (!currentSearch) return true;
            var haystack = [
                row.getAttribute('data-username') || '',
                row.getAttribute('data-email')    || '',
                row.getAttribute('data-role')     || ''
            ].join(' ').toLowerCase();
            return haystack.indexOf(currentSearch) !== -1;
        });
    }

    function setSearch(value) {
        currentSearch = String(value || '').trim().toLowerCase();
        currentPage = 1;
        if (filterSearch && filterSearch.value !== value) filterSearch.value = value;

        /* Mirror to topbar search if present */
        var tb = document.getElementById('topbarSearch');
        if (tb && tb.value !== value) tb.value = value;

        /* Update URL with search parameter */
        var url = new URL(window.location);
        if (currentSearch) {
            url.searchParams.set('search', currentSearch);
        } else {
            url.searchParams.delete('search');
        }
        window.history.pushState({}, '', url);

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
        if (clearBtn) clearBtn.hidden = !(currentSearch || currentRole);

        /* Update filter count in URL */
        var url = new URL(window.location);
        url.searchParams.set('count', total);
        window.history.pushState({}, '', url);

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

    /* Search */
    if (filterSearch) filterSearch.addEventListener('input', function () { setSearch(filterSearch.value); });

    /* Topbar search integration */
    document.addEventListener('topbar:search', function (e) {
        if (!e.detail) return;
        setSearch(e.detail.value);
    });

    /* Role filter (Bootstrap dropdown) */
    document.querySelectorAll('[data-role]').forEach(function (item) {
        item.addEventListener('click', function () {
            currentRole = item.getAttribute('data-role') || '';
            if (roleLabel) roleLabel.textContent = item.textContent.trim();

            document.querySelectorAll('[data-role]').forEach(function (x) {
                x.classList.toggle('active', x === item);
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
                if (x === item && currentRole !== '') {
                    var i = document.createElement('i');
                    i.className = 'bi bi-check2 dropdown-check';
                    x.appendChild(i);
                }
            });

            /* Update URL with role parameter */
            var url = new URL(window.location);
            if (currentRole) {
                url.searchParams.set('role', currentRole);
            } else {
                url.searchParams.delete('role');
            }
            window.history.pushState({}, '', url);

            currentPage = 1;
            render();
        });
    });

    /* Clear filters */
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            currentRole = '';
            if (roleLabel) roleLabel.textContent = 'All roles';
            document.querySelectorAll('[data-role]').forEach(function (x) {
                x.classList.remove('active');
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
                if (x.getAttribute('data-role') === '') {
                    x.classList.add('active');
                    var i = document.createElement('i');
                    i.className = 'bi bi-check2 dropdown-check';
                    x.appendChild(i);
                }
            });
            setSearch('');
        });
    }

    /* Row actions */
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-action');

        if (action === 'copy-email') {
            var email = btn.getAttribute('data-email') || '';
            if (!email) { window.showToast('No email on file', 'bi-envelope-slash', true); return; }
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

    /* ⌘K / Ctrl+K — focus in-page filter */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && String(e.key).toLowerCase() === 'k') {
            e.preventDefault();
            if (filterSearch) filterSearch.focus();
        }
    });

    render();
})();
</script>

<?php
/**
 * ADMIN ACTIVITY LOGS PAGE
 * Controller: AdminActivityLogController::index()
 * Layout    : admin (topbar/sidebar provided by app.php)
 *
 * @var array $logs
 * @var array $stats
 * @var int   $currentPage
 * @var int   $totalPages
 * @var int   $totalLogs
 */

$logs        = $logs        ?? [];
$stats       = $stats       ?? [];
$currentPage = (int) ($currentPage ?? 1);
$totalPages  = (int) ($totalPages  ?? 1);
$totalLogs   = (int) ($totalLogs   ?? 0);

if ($currentPage < 1) $currentPage = 1;
if ($totalPages  < 1) $totalPages  = 1;

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

/* ---------- Action badge registry ---------- */
$actionBadge = static function (string $action): array {
    $a = strtolower(trim($action));
    if ($a === '') return ['label' => '—', 'cls' => 'is-other', 'icon' => 'bi-dot'];

    if (strpos($a, 'create') !== false || strpos($a, 'add') === 0) {
        return ['label' => ucfirst($a), 'cls' => 'is-create', 'icon' => 'bi-plus-circle'];
    }
    if (strpos($a, 'update') !== false || strpos($a, 'edit') !== false || strpos($a, 'modif') !== false) {
        return ['label' => ucfirst($a), 'cls' => 'is-update', 'icon' => 'bi-pencil-square'];
    }
    if (strpos($a, 'delete') !== false || strpos($a, 'remove') !== false) {
        return ['label' => ucfirst($a), 'cls' => 'is-delete', 'icon' => 'bi-trash'];
    }
    if (strpos($a, 'logout') !== false || strpos($a, 'signout') !== false || strpos($a, 'sign_out') !== false) {
        return ['label' => ucfirst($a), 'cls' => 'is-logout', 'icon' => 'bi-box-arrow-right'];
    }
    if (strpos($a, 'login') !== false || strpos($a, 'signin') !== false || strpos($a, 'sign_in') !== false) {
        return ['label' => ucfirst($a), 'cls' => 'is-login', 'icon' => 'bi-box-arrow-in-right'];
    }
    if (strpos($a, 'view') !== false || strpos($a, 'read') !== false) {
        return ['label' => ucfirst($a), 'cls' => 'is-view', 'icon' => 'bi-eye'];
    }
    return ['label' => ucfirst($a), 'cls' => 'is-other', 'icon' => 'bi-circle'];
};

$categorize = static function (string $action): string {
    $a = strtolower(trim($action));
    if ($a === '') return 'other';
    if (strpos($a, 'create') !== false || strpos($a, 'add') === 0) return 'create';
    if (strpos($a, 'update') !== false || strpos($a, 'edit') !== false) return 'update';
    if (strpos($a, 'delete') !== false || strpos($a, 'remove') !== false) return 'delete';
    if (strpos($a, 'logout') !== false || strpos($a, 'signout') !== false) return 'logout';
    if (strpos($a, 'login') !== false || strpos($a, 'signin') !== false) return 'login';
    if (strpos($a, 'view') !== false || strpos($a, 'read') !== false) return 'view';
    return 'other';
};

/* ---------- Time helpers ---------- */
$fmtWhen = static function (string $ts) {
    if ($ts === '') return '—';
    $t = strtotime($ts);
    return $t === false ? $ts : date('M j, Y · g:i A', $t);
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

/* ---------- KPI extraction ---------- */
$totalActivities = $pickNum($stats, ['total', 'total_logs', 'total_activities', 'logs_count'], $totalLogs);
$loginCount      = $pickNum($stats, ['logins', 'login_count', 'total_logins'], 0);
$changeCount     = $pickNum($stats, ['changes', 'data_changes', 'total_changes', 'mutations'], 0);
$todayCount      = $pickNum($stats, ['today', 'today_count', 'actions_today', 'today_logs'], 0);
$uniqueUsers     = $pickNum($stats, ['unique_users', 'unique_actors', 'users_count', 'actors'], 0);

/* Fallback: approximate from current page if stats empty */
if (empty($stats) && !empty($logs)) {
    $loginCount  = 0;
    $changeCount = 0;
    $todayCount  = 0;
    $uniqueSet   = [];
    $today       = date('Y-m-d');

    foreach ($logs as $log) {
        $raw    = (string) ($log['action'] ?? $log['action_type'] ?? '');
        $cat    = $categorize($raw);
        if ($cat === 'login')  $loginCount++;
        if (in_array($cat, ['create', 'update', 'delete'], true)) $changeCount++;
        $when = (string) ($log['created_at'] ?? $log['created_on'] ?? '');
        if ($when !== '' && strpos($when, $today) === 0) $todayCount++;
        $uid = (string) ($log['user_id'] ?? $log['actor_id'] ?? $log['admin_id'] ?? '');
        if ($uid !== '' && !isset($uniqueSet[$uid])) { $uniqueSet[$uid] = true; $uniqueUsers++; }
    }
    $totalActivities = $totalLogs;
}

/* ---------- Pagination range builder (server-side) ---------- */
$buildRange = static function (int $cur, int $total): array {
    $range = [];
    if ($total <= 7) {
        for ($i = 1; $i <= $total; $i++) $range[] = $i;
    } else {
        $range[] = 1;
        if ($cur > 3) $range[] = '…';
        for ($j = max(2, $cur - 1); $j <= min($total - 1, $cur + 1); $j++) $range[] = $j;
        if ($cur < $total - 2) $range[] = '…';
        $range[] = $total;
    }
    return $range;
};
$pageRange = $buildRange($currentPage, $totalPages);

/* ---------- Flash (from session — this controller has none) ---------- */
$flashSuccess = $_SESSION['success'] ?? null;
$flashError   = $_SESSION['error']   ?? null;
$flashAdmin   = $_SESSION['admin_user_flash'] ?? null;
unset($_SESSION['success'], $_SESSION['error'], $_SESSION['admin_user_flash']);

if (is_array($flashAdmin)) {
    $t = (string) ($flashAdmin['type'] ?? '');
    $m = (string) ($flashAdmin['message'] ?? '');
    if ($t === 'danger') $flashError = $m ?: $flashError;
    else                 $flashSuccess = $m ?: $flashSuccess;
}
?>


<style>
    /* ==========================================================
       ACTIVITY LOGS — page-specific styles
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

    /* ---------- Stats strip (5 KPIs) ---------- */
    .stats-strip {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
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
    .stat-dot.is-total   { background: var(--text); }
    .stat-dot.is-login   { background: var(--info); }
    .stat-dot.is-change  { background: var(--warning); }
    .stat-dot.is-today   { background: var(--success); }
    .stat-dot.is-users   { background: var(--text-secondary); }

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

    @media (max-width: 1199.98px) {
        .stats-strip { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .stat-item:nth-child(3n) { border-right: 0; }
        .stat-item:nth-child(n+4) { border-top: 1px solid var(--border); }
    }
    @media (max-width: 575.98px) {
        .stats-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .stat-item:nth-child(3n) { border-right: 1px solid var(--border); }
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

    /* ---------- Bootstrap dropdown theme ---------- */
    #actionFilterBtn { min-width: 170px; justify-content: space-between; }
    @media (max-width: 575.98px) {
        #actionFilterBtn { flex: 1 1 auto; min-width: 0; }
    }

    /* ---------- Action badges ---------- */
    .action-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        letter-spacing: 0.01em;
        text-transform: capitalize;
    }
    .action-badge i { font-size: 12px; }
    .action-badge.is-create { background: var(--success-bg);  color: var(--success); }
    .action-badge.is-update { background: var(--warning-bg);  color: var(--warning); }
    .action-badge.is-delete { background: var(--danger-bg);   color: var(--danger); }
    .action-badge.is-login  { background: var(--info-bg);     color: var(--info); }
    .action-badge.is-logout { background: var(--surface-muted); color: var(--text-secondary); }
    .action-badge.is-view   { background: var(--surface-muted); color: var(--text-secondary); }
    .action-badge.is-other  { background: var(--surface-hover); color: var(--text-secondary); }

    /* ---------- Table ---------- */
    .log-row { transition: background-color 120ms ease; }
    .log-row:hover { background: var(--surface-muted); }

    .actor-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .actor-avatar {
        width: 30px; height: 30px;
        border-radius: 50%;
        background: var(--surface-hover);
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        flex-shrink: 0;
        border: 1px solid var(--border);
    }
    .actor-meta { min-width: 0; flex: 1; }
    .actor-name {
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .actor-sub {
        font-size: 11px;
        color: var(--text-tertiary);
        margin: 1px 0 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .target-cell {
        font-size: 12px;
        color: var(--text-secondary);
        white-space: nowrap;
    }
    .target-cell .entity {
        font-weight: 500;
        color: var(--text);
        text-transform: capitalize;
    }
    .target-cell .entity-id {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        color: var(--text-tertiary);
        font-size: 11px;
    }

    .cell-ip {
        font-size: 12px;
        color: var(--text-secondary);
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        white-space: nowrap;
    }
    .cell-when {
        font-size: 12px;
        color: var(--text-secondary);
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .cell-when .rel { color: var(--text-tertiary); font-size: 11px; }
    .cell-details {
        font-size: 12px;
        color: var(--text-secondary);
        max-width: 280px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cell-actions { width: 44px; text-align: right; }
    .row-actions-btn { width: 28px; height: 28px; font-size: 14px; }

    /* ---------- Pagination (server-side <a> tags) ---------- */
    .pagination a {
        width: 28px;
        height: 28px;
        border: 0;
        border-radius: 5px;
        background: transparent;
        color: var(--text-secondary);
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background-color 120ms ease;
    }
    .pagination a:hover:not(.is-disabled) {
        background: var(--surface-hover);
        color: var(--text);
    }
    .pagination a.active {
        background: var(--accent);
        color: #fff;
    }
    .pagination a.is-disabled {
        color: var(--text-tertiary);
        pointer-events: none;
        cursor: not-allowed;
    }
    .pagination a i { font-size: 11px; }
    .pagination span {
        display: inline-flex;
        width: 28px;
        height: 28px;
        align-items: center;
        justify-content: center;
        color: var(--text-tertiary);
        font-size: 12px;
    }

    /* ---------- Empty ---------- */
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

    /* ---------- Detail modal layout ---------- */
    .detail-grid {
        display: grid;
        grid-template-columns: 130px 1fr;
        gap: 12px 16px;
    }
    .detail-label {
        font-size: 11px;
        font-weight: 500;
        color: var(--text-tertiary);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin: 0;
        padding-top: 2px;
    }
    .detail-value {
        font-size: 13px;
        color: var(--text);
        margin: 0;
        word-break: break-word;
        min-width: 0;
    }
    .detail-value.is-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
    .detail-value.is-muted { color: var(--text-secondary); }
    .detail-value.is-block { white-space: pre-wrap; padding: 10px 12px; background: var(--surface-muted); border-radius: 6px; font-size: 12px; }

    /* ---------- Toast ---------- */
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
            <h1 class="page-title" id="pageTitle">Activity logs</h1>
            <p class="page-subtitle">
                Every action taken across your workspace
                <span class="date-chip">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                    Page <?= $currentPage ?> of <?= $totalPages ?> · <?= number_format($totalLogs) ?> total
                </span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn-secondary" id="exportCsvBtn">
                <i class="bi bi-download" aria-hidden="true"></i>Export CSV
            </button>
        </div>
    </section>

    <!-- ==========================================================
         Stats strip (5 KPIs)
         ========================================================== -->
    <section class="stats-strip" aria-label="Activity statistics">
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-total"></span>
                <p class="stat-label">Total activities</p>
            </div>
            <p class="stat-value"><?= number_format($totalActivities) ?></p>
            <p class="stat-hint">Across all time</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-login"></span>
                <p class="stat-label">Logins</p>
            </div>
            <p class="stat-value"><?= number_format($loginCount) ?></p>
            <p class="stat-hint">Authentication events</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-change"></span>
                <p class="stat-label">Data changes</p>
            </div>
            <p class="stat-value"><?= number_format($changeCount) ?></p>
            <p class="stat-hint">Creates · updates · deletes</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-today"></span>
                <p class="stat-label">Today</p>
            </div>
            <p class="stat-value"><?= number_format($todayCount) ?></p>
            <p class="stat-hint <?= $todayCount > 0 ? 'is-up' : '' ?>">
                <?= $todayCount > 0 ? 'Active today' : 'No activity yet' ?>
            </p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-users"></span>
                <p class="stat-label">Unique actors</p>
            </div>
            <p class="stat-value"><?= number_format($uniqueUsers) ?></p>
            <p class="stat-hint">Distinct users</p>
        </div>
    </section>

    <!-- ==========================================================
         Filter bar
         ========================================================== -->
    <section class="filter-bar" aria-label="Filter activity logs">
        <div class="filter-bar__group">
            <div class="search-wrap filter-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search"
                       id="filterSearch"
                       class="search-input"
                       placeholder="Filter by actor, action, target or IP..."
                       aria-label="Filter activity logs"
                       autocomplete="off">
            </div>

            <!-- Action filter — Bootstrap dropdown -->
            <div class="dropdown">
                <button type="button" class="btn-secondary" id="actionFilterBtn"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-inline-flex align-items-center gap-2">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span id="actionFilterLabel">All actions</span>
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="actionFilterBtn">
                    <li><div class="dropdown-label-app">Action</div></li>
                    <li>
                        <button type="button" class="dropdown-item active" data-action="">
                            <i class="bi bi-list-ul" aria-hidden="true"></i>All actions
                            <i class="bi bi-check2 dropdown-check" aria-hidden="true"></i>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item" data-action="create"><i class="bi bi-plus-circle" aria-hidden="true"></i>Creates</button></li>
                    <li><button type="button" class="dropdown-item" data-action="update"><i class="bi bi-pencil-square" aria-hidden="true"></i>Updates</button></li>
                    <li><button type="button" class="dropdown-item" data-action="delete"><i class="bi bi-trash" aria-hidden="true"></i>Deletes</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item" data-action="login"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Logins</button></li>
                    <li><button type="button" class="dropdown-item" data-action="logout"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Logouts</button></li>
                    <li><button type="button" class="dropdown-item" data-action="view"><i class="bi bi-eye" aria-hidden="true"></i>Views</button></li>
                    <li><button type="button" class="dropdown-item" data-action="other"><i class="bi bi-circle" aria-hidden="true"></i>Other</button></li>
                </ul>
            </div>
        </div>

        <div class="filter-bar__group">
            <span class="filter-count" id="filterCount"><?= number_format(count($logs)) ?> entr<?= count($logs) === 1 ? 'y' : 'ies' ?> on this page</span>
            <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
                <i class="bi bi-x" aria-hidden="true"></i>Clear
            </button>
        </div>
    </section>

    <!-- ==========================================================
         Logs table
         ========================================================== -->
    <section class="section-gap">
        <div class="panel">

            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></div>
                    <h3 class="empty-title">No activity logs yet</h3>
                    <p class="empty-text">
                        <?= $currentPage > 1
                            ? 'This page has no records. Try going back to page 1.'
                            : 'Activity will appear here as admins and users take action across the system.' ?>
                    </p>
                    <?php if ($currentPage > 1): ?>
                        <a href="/admin/activity-logs" class="btn-secondary">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i>Back to page 1
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table activity-table align-middle">
                        <caption>Activity logs: actor, action, target, IP address, details and timestamp.</caption>
                        <thead>
                            <tr>
                                <th scope="col">When</th>
                                <th scope="col">Actor</th>
                                <th scope="col">Action</th>
                                <th scope="col">Target</th>
                                <th scope="col">IP</th>
                                <th scope="col">Details</th>
                                <th scope="col" style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody id="logsBody">
                            <?php foreach ($logs as $log):
                                $lid     = (int) ($log['id'] ?? 0);
                                $actorId = $pickStr($log, ['user_id', 'actor_id', 'admin_id'], '');
                                $actor   = $pickStr($log, ['username', 'user_name', 'actor_name', 'admin_name', 'name'], 'System');
                                $email   = $pickStr($log, ['email', 'user_email', 'actor_email', 'email_address'], '');
                                $raw     = $pickStr($log, ['action', 'action_type', 'event', 'type'], '');
                                $entity  = $pickStr($log, ['entity_type', 'target_type', 'model', 'resource'], '');
                                $entityId= $pickStr($log, ['entity_id', 'target_id', 'record_id', 'object_id'], '');
                                $details = $pickStr($log, ['description', 'details', 'message', 'notes', 'summary'], '');
                                $ip      = $pickStr($log, ['ip_address', 'ip', 'ip_addr', 'remote_addr'], '');
                                $ua      = $pickStr($log, ['user_agent', 'agent'], '');
                                $when    = $pickStr($log, ['created_at', 'created_on', 'timestamp', 'date', 'when'], '');

                                $meta    = $actionBadge($raw);
                                $group   = $categorize($raw);
                                $initial = strtoupper(substr($actor !== '' ? $actor : '?', 0, 1));

                                $targetText = '—';
                                if ($entity !== '' || $entityId !== '') {
                                    $targetText = ucfirst(str_replace('_', ' ', $entity !== '' ? $entity : 'record'))
                                                . ($entityId !== '' ? ' #' . $entityId : '');
                                }

                                $searchBlob = strtolower($actor . ' ' . $email . ' ' . $raw . ' ' . $targetText . ' ' . $details . ' ' . $ip);
                            ?>
                                <tr class="log-row"
                                    data-log-id="<?= $lid ?>"
                                    data-action-group="<?= $e($group) ?>"
                                    data-action-raw="<?= $e(strtolower($raw)) ?>"
                                    data-actor="<?= $e(strtolower($actor)) ?>"
                                    data-search="<?= $e($searchBlob) ?>">
                                    <td class="cell-when">
                                        <?= $e($fmtWhen($when)) ?>
                                        <?php if ($when !== ''): ?>
                                            <div class="rel"><?= $e($fmtRelative($when)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actor-cell">
                                            <span class="actor-avatar" aria-hidden="true"><?= $e($initial) ?></span>
                                            <div class="actor-meta">
                                                <p class="actor-name"><?= $e($actor) ?></p>
                                                <?php if ($email !== ''): ?>
                                                    <p class="actor-sub"><?= $e($email) ?></p>
                                                <?php elseif ($actorId !== ''): ?>
                                                    <p class="actor-sub">ID #<?= $e($actorId) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="action-badge <?= $e($meta['cls']) ?>">
                                            <i class="bi <?= $e($meta['icon']) ?>" aria-hidden="true"></i>
                                            <?= $e($raw !== '' ? $raw : '—') ?>
                                        </span>
                                    </td>
                                    <td class="target-cell">
                                        <?php if ($entity !== '' || $entityId !== ''): ?>
                                            <span class="entity"><?= $e($entity !== '' ? $entity : 'record') ?></span>
                                            <?php if ($entityId !== ''): ?>
                                                <span class="entity-id">#<?= $e($entityId) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-ip"><?= $ip !== '' ? $e($ip) : '—' ?></td>
                                    <td class="cell-details" title="<?= $e($details) ?>">
                                        <?= $details !== '' ? $e($details) : '—' ?>
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
                                                    <button type="button" class="dropdown-item"
                                                            data-action="view-detail"
                                                            data-log-id="<?= $lid ?>"
                                                            data-log-action="<?= $e($raw) ?>"
                                                            data-log-actor="<?= $e($actor) ?>"
                                                            data-log-email="<?= $e($email) ?>"
                                                            data-log-actor-id="<?= $e($actorId) ?>"
                                                            data-log-target="<?= $e($targetText) ?>"
                                                            data-log-ip="<?= $e($ip) ?>"
                                                            data-log-ua="<?= $e($ua) ?>"
                                                            data-log-when="<?= $e($fmtWhen($when)) ?>"
                                                            data-log-relative="<?= $e($fmtRelative($when)) ?>"
                                                            data-log-details="<?= $e($details) ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#logDetailModal">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>View details
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item"
                                                            data-action="copy-id"
                                                            data-log-id="<?= $lid ?>">
                                                        <i class="bi bi-clipboard" aria-hidden="true"></i>Copy log ID
                                                    </button>
                                                </li>
                                                <?php if ($email !== ''): ?>
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                                data-action="copy-email"
                                                                data-email="<?= $e($email) ?>">
                                                            <i class="bi bi-envelope" aria-hidden="true"></i>Copy actor email
                                                        </button>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="no-results" id="noResults" role="status">No logs match your filters on this page.</div>

                <div class="table-footer">
                    <span id="showingLabel">
                        Page <?= $currentPage ?> of <?= $totalPages ?>
                        · <?= number_format($totalLogs) ?> total <?= $totalLogs === 1 ? 'entry' : 'entries' ?>
                    </span>
                    <nav class="pagination" id="pagination" aria-label="Pagination">
                        <?php
                        $prevDisabled = $currentPage <= 1;
                        $nextDisabled = $currentPage >= $totalPages;
                        ?>
                        <a href="?page=<?= max(1, $currentPage - 1) ?>"
                           class="<?= $prevDisabled ? 'is-disabled' : '' ?>"
                           aria-label="Previous page"
                           <?= $prevDisabled ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </a>

                        <?php foreach ($pageRange as $p): ?>
                            <?php if ($p === '…'): ?>
                                <span aria-hidden="true">…</span>
                            <?php else: ?>
                                <a href="?page=<?= (int) $p ?>"
                                   class="<?= (int) $p === $currentPage ? 'active' : '' ?>"
                                   <?= (int) $p === $currentPage ? 'aria-current="page"' : '' ?>>
                                    <?= (int) $p ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <a href="?page=<?= min($totalPages, $currentPage + 1) ?>"
                           class="<?= $nextDisabled ? 'is-disabled' : '' ?>"
                           aria-label="Next page"
                           <?= $nextDisabled ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </nav>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>


<!-- ==========================================================
     MODAL: Log detail
     ========================================================== -->
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-app">
            <div class="modal-header-app">
                <div>
                    <h2 class="modal-title-app" id="logDetailTitle">Log detail</h2>
                    <p class="modal-subtitle-app">Full record of this activity</p>
                </div>
                <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-body-app">
                <div class="detail-grid">
                    <p class="detail-label">Log ID</p>
                    <p class="detail-value is-mono" id="d-id">—</p>

                    <p class="detail-label">Action</p>
                    <p class="detail-value" id="d-action">—</p>

                    <p class="detail-label">Actor</p>
                    <p class="detail-value" id="d-actor">—</p>

                    <p class="detail-label">Email</p>
                    <p class="detail-value is-muted" id="d-email">—</p>

                    <p class="detail-label">Target</p>
                    <p class="detail-value" id="d-target">—</p>



                    <p class="detail-label">When</p>
                    <p class="detail-value" id="d-when">—</p>

                    <p class="detail-label">Details</p>
                    <p class="detail-value is-muted is-block" id="d-details" style="grid-column:2">—</p>
                </div>
            </div>

            <div class="modal-footer-app">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
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
   FLASH
   ========================================================= */
<?php if ($flashSuccess !== null && $flashSuccess !== ''): ?>
    document.addEventListener('DOMContentLoaded', function () {
        window.showToast(<?= json_encode($flashSuccess) ?>, 'bi-check2-circle');
    });
<?php endif; ?>
<?php if ($flashError !== null && $flashError !== ''): ?>
    document.addEventListener('DOMContentLoaded', function () {
        window.showToast(<?= json_encode($flashError) ?>, 'bi-exclamation-circle', true);
    });
<?php endif; ?>

/* =========================================================
   DETAIL MODAL — populate from trigger button
   ========================================================= */
(function () {
    var modal = document.getElementById('logDetailModal');
    if (!modal) return;

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '—';
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;

        setText('d-id',      '#' + (btn.getAttribute('data-log-id') || '—'));
        setText('d-action',  btn.getAttribute('data-log-action'));
        setText('d-actor',   btn.getAttribute('data-log-actor'));
        setText('d-email',   btn.getAttribute('data-log-email'));
        setText('d-target',  btn.getAttribute('data-log-target'));
        setText('d-details', btn.getAttribute('data-log-details'));

        var when = btn.getAttribute('data-log-when') || '';
        var rel  = btn.getAttribute('data-log-relative') || '';
        setText('d-when', when + (rel && when ? ' · ' + rel : ''));
    });
})();

/* =========================================================
   LOGS TABLE — client-side search + action filter
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
        var match = document.querySelector('[data-action="' + currentAction + '"]');
        if (match && actionLabel) {
            actionLabel.textContent = match.textContent.trim();
            document.querySelectorAll('[data-action]').forEach(function (x) {
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

    /* Action filter */
    document.querySelectorAll('[data-action]').forEach(function (item) {
        item.addEventListener('click', function () {
            currentAction = (item.getAttribute('data-action') || '').toLowerCase();
            if (actionLabel) actionLabel.textContent = item.textContent.trim();

            document.querySelectorAll('[data-action]').forEach(function (x) {
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
            document.querySelectorAll('[data-action]').forEach(function (x) {
                x.classList.remove('active');
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
            });
            var allBtn = document.querySelector('[data-action=""]');
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

    /* Export CSV — current page (respects active filters) */
    var exportBtn = document.getElementById('exportCsvBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var matched = filteredRows();
            if (!matched.length) { window.showToast('Nothing to export', 'bi-download', true); return; }

            var rows = [['Log ID', 'Action', 'Actor', 'Email', 'Target', 'IP', 'When', 'Details']];
            matched.forEach(function (row) {
                var trigger = row.querySelector('[data-action="view-detail"]');
                if (!trigger) return;
                rows.push([
                    trigger.getAttribute('data-log-id') || '',
                    trigger.getAttribute('data-log-action') || '',
                    trigger.getAttribute('data-log-actor') || '',
                    trigger.getAttribute('data-log-email') || '',
                    trigger.getAttribute('data-log-target') || '',
                    trigger.getAttribute('data-log-ip') || '',
                    trigger.getAttribute('data-log-when') || '',
                    trigger.getAttribute('data-log-details') || ''
                ]);
            });

            var csv = rows.map(function (r) {
                return r.map(function (v) {
                    var s = String(v).replace(/"/g, '""');
                    return /[",\n]/.test(s) ? '"' + s + '"' : s;
                }).join(',');
            }).join('\n');

            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href = url;
            a.download = 'activity-logs-page-<?= (int) $currentPage ?>-' + Date.now() + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            window.showToast('Exported ' + matched.length + ' row' + (matched.length === 1 ? '' : 's'), 'bi-download');
        });
    }

    /* Initial render */
    render();
    syncPaginationLinks();
})();
</script>

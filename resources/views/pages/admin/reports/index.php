<?php
/**
 * ADMIN REPORTS MANAGEMENT PAGE
 * Controller: AdminReportController::index() / ::update()
 * Layout    : admin (topbar/sidebar come from app.php)
 *
 * @var array       $reports
 * @var array       $stats
 * @var array|null  $flash       ['type' => 'success'|'danger', 'message' => string]
 * @var string      $csrfToken
 */

$reports   = $reports   ?? [];
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

/* ---------- Status registry ---------- */
$statusMeta = static function (string $s): array {
    $u = strtoupper(trim($s));
    switch ($u) {
        case 'PENDING':
        case 'OPEN':
        case 'NEW':
            return ['label' => 'Pending',     'cls' => 'is-pending',     'icon' => 'bi-hourglass-split',      'key' => 'pending'];
        case 'IN_PROGRESS':
        case 'IN PROGRESS':
        case 'REVIEWING':
        case 'PROCESSING':
            return ['label' => 'In progress', 'cls' => 'is-progress',    'icon' => 'bi-arrow-repeat',         'key' => 'progress'];
        case 'RESOLVED':
        case 'CLOSED':
        case 'DONE':
        case 'COMPLETED':
            return ['label' => 'Resolved',    'cls' => 'is-resolved',    'icon' => 'bi-check2-circle',        'key' => 'resolved'];
        case 'DISMISSED':
        case 'REJECTED':
        case 'IGNORED':
            return ['label' => 'Dismissed',   'cls' => 'is-dismissed',   'icon' => 'bi-slash-circle',         'key' => 'dismissed'];
        default:
            return ['label' => $u !== '' ? ucfirst(strtolower($u)) : 'Unknown',
                    'cls'   => 'is-other',    'icon' => 'bi-circle',    'key' => 'other'];
    }
};

$priorityMeta = static function (string $p): array {
    $u = strtoupper(trim($p));
    switch ($u) {
        case 'URGENT':
        case 'CRITICAL':
        case 'HIGH':
            return ['label' => ucfirst(strtolower($u)), 'cls' => 'is-high'];
        case 'MEDIUM':
        case 'NORMAL':
            return ['label' => 'Medium', 'cls' => 'is-medium'];
        case 'LOW':
        case 'MINOR':
            return ['label' => 'Low',    'cls' => 'is-low'];
        default:
            return ['label' => $u !== '' ? ucfirst(strtolower($u)) : 'Normal', 'cls' => 'is-medium'];
    }
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

/* ---------- KPI extraction (defensive) ---------- */
$totalReports   = $pickNum($stats, ['total', 'total_reports', 'reports_count', 'totalReports'], 0);
$pendingReports = $pickNum($stats, ['pending', 'pending_reports', 'open_reports', 'open'], 0);
$progressCount  = $pickNum($stats, ['in_progress', 'progress', 'reviewing', 'processing'], 0);
$resolvedCount  = $pickNum($stats, ['resolved', 'resolved_reports', 'closed', 'completed'], 0);
$todayCount     = $pickNum($stats, ['today', 'today_count', 'reports_today'], 0);

/* Fallback from current data set when stats empty */
if ($totalReports === 0 && !empty($reports)) {
    $totalReports   = count($reports);
    $pendingReports = 0;
    $progressCount  = 0;
    $resolvedCount  = 0;
    $todayCount     = 0;
    $today          = date('Y-m-d');

    foreach ($reports as $r) {
        $meta = $statusMeta((string) ($r['status'] ?? ''));
        if ($meta['key'] === 'pending')  $pendingReports++;
        if ($meta['key'] === 'progress') $progressCount++;
        if ($meta['key'] === 'resolved') $resolvedCount++;
        $when = (string) ($r['created_at'] ?? $r['created_on'] ?? '');
        if ($when !== '' && strpos($when, $today) === 0) $todayCount++;
    }
}

/* ---------- Flash ---------- */
$flashType = (string) ($flash['type'] ?? '');
$flashMsg  = (string) ($flash['message'] ?? '');
?>

<style>
    /* ==========================================================
       ADMIN REPORTS — page-specific styles
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
    .stat-dot.is-total    { background: var(--text); }
    .stat-dot.is-pending  { background: var(--warning); }
    .stat-dot.is-progress { background: var(--info); }
    .stat-dot.is-resolved { background: var(--success); }
    .stat-dot.is-today    { background: var(--text-secondary); }

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
    .stat-value.is-pending  { color: var(--warning); }
    .stat-value.is-progress { color: var(--info); }
    .stat-value.is-resolved { color: var(--success); }

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

    /* ---------- Filter bar ---------- */
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
    #statusFilterBtn { min-width: 170px; justify-content: space-between; }
    @media (max-width: 575.98px) {
        #statusFilterBtn { flex: 1 1 auto; min-width: 0; }
    }

    /* ---------- Status / priority badges ---------- */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
        letter-spacing: 0.01em;
    }
    .status-badge i { font-size: 12px; }
    .status-badge.is-pending   { background: var(--warning-bg);    color: var(--warning); }
    .status-badge.is-progress  { background: var(--info-bg);       color: var(--info); }
    .status-badge.is-resolved  { background: var(--success-bg);    color: var(--success); }
    .status-badge.is-dismissed { background: var(--danger-bg);     color: var(--danger); }
    .status-badge.is-other     { background: var(--surface-hover); color: var(--text-secondary); }

    .priority-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
        white-space: nowrap;
    }
    .priority-badge.is-high   { background: var(--danger-bg);  color: var(--danger); }
    .priority-badge.is-medium { background: var(--warning-bg); color: var(--warning); }
    .priority-badge.is-low    { background: var(--surface-muted); color: var(--text-secondary); }

    /* ---------- Report rows ---------- */
    .report-row { transition: background-color 120ms ease; }
    .report-row:hover { background: var(--surface-muted); }

    .subject-cell { min-width: 0; max-width: 360px; }
    .subject-title {
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .subject-desc {
        font-size: 11px;
        color: var(--text-tertiary);
        margin: 2px 0 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .reporter-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .reporter-avatar {
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
    .reporter-meta { min-width: 0; flex: 1; }
    .reporter-name {
        font-size: 12px;
        font-weight: 500;
        color: var(--text);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .reporter-sub {
        font-size: 11px;
        color: var(--text-tertiary);
        margin: 1px 0 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

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

    /* ---------- Update modal ---------- */
    .report-preview {
        background: var(--surface-muted);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 16px;
    }
    .report-preview-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
        margin: 0 0 4px;
    }
    .report-preview-meta {
        font-size: 11px;
        color: var(--text-tertiary);
        margin: 0;
    }
    .report-preview-body {
        font-size: 12px;
        color: var(--text-secondary);
        margin: 8px 0 0;
        line-height: 1.55;
        max-height: 120px;
        overflow-y: auto;
        white-space: pre-wrap;
    }
    .form-control-app[rows] {
        height: auto;
        padding: 8px 12px;
        resize: vertical;
        line-height: 1.5;
    }

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
            <h1 class="page-title" id="pageTitle">Reports management</h1>
            <p class="page-subtitle">
                Review and respond to reports submitted across your workspace
                <span class="date-chip">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <?= date('l, F j, Y') ?>
                </span>
            </p>
        </div>
    </section>

    <!-- ==========================================================
         Stats strip
         ========================================================== -->
    <section class="stats-strip" aria-label="Report statistics">
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-total"></span>
                <p class="stat-label">Total reports</p>
            </div>
            <p class="stat-value"><?= number_format($totalReports) ?></p>
            <p class="stat-hint">All time</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-pending"></span>
                <p class="stat-label">Pending</p>
            </div>
            <p class="stat-value is-pending"><?= number_format($pendingReports) ?></p>
            <p class="stat-hint">Awaiting review</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-progress"></span>
                <p class="stat-label">In progress</p>
            </div>
            <p class="stat-value is-progress"><?= number_format($progressCount) ?></p>
            <p class="stat-hint">Being handled</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-resolved"></span>
                <p class="stat-label">Resolved</p>
            </div>
            <p class="stat-value is-resolved"><?= number_format($resolvedCount) ?></p>
            <p class="stat-hint">Closed & archived</p>
        </div>
        <div class="stat-item">
            <div class="stat-top">
                <span class="stat-dot is-today"></span>
                <p class="stat-label">Today</p>
            </div>
            <p class="stat-value"><?= number_format($todayCount) ?></p>
            <p class="stat-hint <?= $todayCount > 0 ? 'is-up' : '' ?>">
                <?= $todayCount > 0 ? 'New today' : 'None yet' ?>
            </p>
        </div>
    </section>

    <!-- ==========================================================
         Filter bar
         ========================================================== -->
    <section class="filter-bar" aria-label="Filter reports">
        <div class="filter-bar__group">
            <div class="search-wrap filter-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search"
                       id="filterSearch"
                       class="search-input"
                       placeholder="Filter by subject, reporter or details..."
                       aria-label="Filter reports"
                       autocomplete="off">
            </div>

            <!-- Status filter — Bootstrap dropdown -->
            <div class="dropdown">
                <button type="button" class="btn-secondary" id="statusFilterBtn"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-inline-flex align-items-center gap-2">
                        <i class="bi bi-funnel" aria-hidden="true"></i>
                        <span id="statusFilterLabel">All statuses</span>
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:10px" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="statusFilterBtn">
                    <li><div class="dropdown-label-app">Status</div></li>
                    <li>
                        <button type="button" class="dropdown-item active" data-status="">
                            <i class="bi bi-list-ul" aria-hidden="true"></i>All statuses
                            <i class="bi bi-check2 dropdown-check" aria-hidden="true"></i>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="button" class="dropdown-item" data-status="pending"><i class="bi bi-hourglass-split" aria-hidden="true"></i>Pending</button></li>
                    <li><button type="button" class="dropdown-item" data-status="progress"><i class="bi bi-arrow-repeat" aria-hidden="true"></i>In progress</button></li>
                    <li><button type="button" class="dropdown-item" data-status="resolved"><i class="bi bi-check2-circle" aria-hidden="true"></i>Resolved</button></li>
                    <li><button type="button" class="dropdown-item" data-status="dismissed"><i class="bi bi-slash-circle" aria-hidden="true"></i>Dismissed</button></li>
                </ul>
            </div>
        </div>

        <div class="filter-bar__group">
            <span class="filter-count" id="filterCount"><?= number_format(count($reports)) ?> report<?= count($reports) === 1 ? '' : 's' ?></span>
            <button type="button" class="btn-text" id="clearFiltersBtn" hidden>
                <i class="bi bi-x" aria-hidden="true"></i>Clear
            </button>
        </div>
    </section>

    <!-- ==========================================================
         Reports table
         ========================================================== -->
    <section class="section-gap">
        <div class="panel">

            <?php if (empty($reports)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-flag" aria-hidden="true"></i></div>
                    <h3 class="empty-title">No reports yet</h3>
                    <p class="empty-text">Reports submitted by users will appear here for review and response.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table activity-table align-middle">
                        <caption>Reports: subject, reporter, priority, status, submitted date and actions.</caption>
                        <thead>
                            <tr>
                                <th scope="col">Subject</th>
                                <th scope="col">Reporter</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Status</th>
                                <th scope="col">Submitted</th>
                                <th scope="col" style="width:44px"></th>
                            </tr>
                        </thead>
                        <tbody id="reportsBody">
                            <?php foreach ($reports as $r):
                                $rid        = (int) ($r['id'] ?? 0);
                                $subject    = $pickStr($r, ['subject', 'title', 'reason', 'topic'], 'Report #' . $rid);
                                $desc       = $pickStr($r, ['description', 'content', 'message', 'details', 'body'], '');
                                $type       = $pickStr($r, ['type', 'category', 'report_type', 'kind'], '');
                                $statusRaw  = $pickStr($r, ['status', 'state'], 'PENDING');
                                $priorityRaw = $pickStr($r, ['priority', 'severity', 'urgency'], 'MEDIUM');
                                $reporter   = $pickStr($r, ['username', 'user_name', 'reporter_name', 'reporter', 'name'], 'User #' . $pickStr($r, ['user_id', 'reporter_id'], '?'));
                                $reporterId = $pickStr($r, ['user_id', 'reporter_id', 'actor_id'], '');
                                $email      = $pickStr($r, ['email', 'user_email', 'reporter_email'], '');
                                $when       = $pickStr($r, ['created_at', 'created_on', 'submitted_at', 'date', 'when'], '');
                                $response   = $pickStr($r, ['admin_response', 'response', 'reply', 'admin_reply'], '');

                                $sMeta  = $statusMeta($statusRaw);
                                $pMeta  = $priorityMeta($priorityRaw);
                                $initial = strtoupper(substr($reporter !== '' ? $reporter : '?', 0, 1));
                                $shortDesc = $desc !== '' ? mb_substr($desc, 0, 90) . (mb_strlen($desc) > 90 ? '…' : '') : '';
                            ?>
                                <tr class="report-row"
                                    data-report-id="<?= $rid ?>"
                                    data-status="<?= $e($sMeta['key']) ?>"
                                    data-subject="<?= $e($subject) ?>"
                                    data-reporter="<?= $e($reporter) ?>"
                                    data-search="<?= $e(strtolower($subject . ' ' . $reporter . ' ' . $email . ' ' . $type . ' ' . $desc)) ?>">
                                    <td class="subject-cell">
                                        <p class="subject-title"><?= $e($subject) ?></p>
                                        <?php if ($shortDesc !== ''): ?>
                                            <p class="subject-desc" title="<?= $e($desc) ?>">
                                                <?= $e($shortDesc) ?>
                                            </p>
                                        <?php elseif ($type !== ''): ?>
                                            <p class="subject-desc"><?= $e(ucfirst(str_replace('_', ' ', $type))) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="reporter-cell">
                                            <span class="reporter-avatar" aria-hidden="true"><?= $e($initial) ?></span>
                                            <div class="reporter-meta">
                                                <p class="reporter-name"><?= $e($reporter) ?></p>
                                                <?php if ($email !== ''): ?>
                                                    <p class="reporter-sub"><?= $e($email) ?></p>
                                                <?php elseif ($reporterId !== ''): ?>
                                                    <p class="reporter-sub">ID #<?= $e($reporterId) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="priority-badge <?= $e($pMeta['cls']) ?>"><?= $e($pMeta['label']) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $e($sMeta['cls']) ?>">
                                            <i class="bi <?= $e($sMeta['icon']) ?>" aria-hidden="true"></i>
                                            <?= $e($sMeta['label']) ?>
                                        </span>
                                    </td>
                                    <td class="cell-when">
                                        <?= $e($fmtWhen($when)) ?>
                                        <?php if ($when !== ''): ?>
                                            <div class="rel"><?= $e($fmtRelative($when)) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-actions">
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
                                                            data-action="manage"
                                                            data-report-id="<?= $rid ?>"
                                                            data-report-subject="<?= $e($subject) ?>"
                                                            data-report-reporter="<?= $e($reporter) ?>"
                                                            data-report-email="<?= $e($email) ?>"
                                                            data-report-when="<?= $e($fmtWhen($when)) ?>"
                                                            data-report-type="<?= $e($type) ?>"
                                                            data-report-priority="<?= $e($priorityRaw) ?>"
                                                            data-report-status="<?= $e(strtoupper($statusRaw)) ?>"
                                                            data-report-desc="<?= $e($desc) ?>"
                                                            data-report-response="<?= $e($response) ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#manageReportModal">
                                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>Manage report
                                                    </button>
                                                </li>
                                                <?php if ($email !== ''): ?>
                                                    <li>
                                                        <button type="button" class="dropdown-item"
                                                                data-action="copy-email"
                                                                data-email="<?= $e($email) ?>">
                                                            <i class="bi bi-envelope" aria-hidden="true"></i>Copy reporter email
                                                        </button>
                                                    </li>
                                                <?php endif; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="/admin/users?id=<?= (int) $reporterId ?>" target="_blank" rel="noopener">
                                                        <i class="bi bi-person" aria-hidden="true"></i>Open reporter profile
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="no-results" id="noResults" role="status">No reports match your filters.</div>

                <div class="table-footer">
                    <span id="showingLabel">
                        Showing 1–<?= number_format(count($reports)) ?> of <?= number_format(count($reports)) ?>
                    </span>
                    <div class="pagination" id="pagination" aria-label="Pagination"></div>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>


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
   MANAGE MODAL — populate from the trigger button
   ========================================================= */
(function () {
    var modal = document.getElementById('manageReportModal');
    if (!modal) return;

    function setText(id, value, fallback) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || fallback || '—';
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;

        document.getElementById('mr-id').value = btn.getAttribute('data-report-id') || '';

        setText('mr-subject',  btn.getAttribute('data-report-subject'));
        setText('mr-reporter', btn.getAttribute('data-report-reporter'));
        setText('mr-when',     btn.getAttribute('data-report-when'));
        setText('mr-desc',     btn.getAttribute('data-report-desc'), 'No description provided.');

        var status = (btn.getAttribute('data-report-status') || 'PENDING').toUpperCase();
        var sel = document.getElementById('mr-status');
        if (sel) {
            var found = false;
            Array.prototype.forEach.call(sel.options, function (opt) {
                if (opt.value === status) { opt.selected = true; found = true; }
            });
            if (!found) {
                /* Normalise common variants to the controller's expected values */
                var map = { 'OPEN': 'PENDING', 'NEW': 'PENDING', 'PROCESSING': 'IN_PROGRESS', 'REVIEWING': 'IN_PROGRESS', 'CLOSED': 'RESOLVED', 'DONE': 'RESOLVED', 'COMPLETED': 'RESOLVED' };
                if (map[status] && sel.querySelector('option[value="' + map[status] + '"]')) {
                    sel.value = map[status];
                } else {
                    sel.value = 'PENDING';
                }
            }
        }

        var resp = document.getElementById('mr-response');
        if (resp) resp.value = btn.getAttribute('data-report-response') || '';
    });
})();

/* =========================================================
   REPORTS TABLE — client-side search + status filter + pagination
   ========================================================= */
(function () {
    var body = document.getElementById('reportsBody');
    if (!body) return;

    var empty        = document.getElementById('noResults');
    var showing      = document.getElementById('showingLabel');
    var countEl      = document.getElementById('filterCount');
    var pagination   = document.getElementById('pagination');
    var filterSearch = document.getElementById('filterSearch');
    var clearBtn     = document.getElementById('clearFiltersBtn');
    var statusLabel  = document.getElementById('statusFilterLabel');

    var PAGE_SIZE = 12;
    var currentSearch = '';
    var currentStatus = '';
    var currentPage   = 1;

    var allRows = Array.prototype.slice.call(body.querySelectorAll('tr.report-row'));

    function filteredRows() {
        return allRows.filter(function (row) {
            if (currentStatus && row.getAttribute('data-status') !== currentStatus) return false;
            if (!currentSearch) return true;
            var haystack = (row.getAttribute('data-search') || '').toLowerCase();
            return haystack.indexOf(currentSearch) !== -1;
        });
    }

    function setSearch(value) {
        currentSearch = String(value || '').trim().toLowerCase();
        currentPage = 1;
        if (filterSearch && filterSearch.value !== value) filterSearch.value = value;
        var tb = document.getElementById('topbarSearch');
        if (tb && tb.value !== value) tb.value = value;
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
        if (countEl) countEl.textContent = total + ' report' + (total === 1 ? '' : 's');
        if (clearBtn) clearBtn.hidden = !(currentSearch || currentStatus);

        if (showing) {
            showing.textContent = total === 0
                ? 'No reports'
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

    /* Status filter */
    document.querySelectorAll('[data-status]').forEach(function (item) {
        item.addEventListener('click', function () {
            currentStatus = (item.getAttribute('data-status') || '').toLowerCase();
            if (statusLabel) statusLabel.textContent = item.textContent.trim();

            document.querySelectorAll('[data-status]').forEach(function (x) {
                x.classList.toggle('active', x === item);
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
            });
            if (currentStatus !== '') {
                var i = document.createElement('i');
                i.className = 'bi bi-check2 dropdown-check';
                item.appendChild(i);
            }

            currentPage = 1;
            render();
        });
    });

    /* Clear filters */
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            currentStatus = '';
            if (statusLabel) statusLabel.textContent = 'All statuses';
            document.querySelectorAll('[data-status]').forEach(function (x) {
                x.classList.remove('active');
                var check = x.querySelector('.dropdown-check');
                if (check) check.remove();
            });
            var allBtn = document.querySelector('[data-status=""]');
            if (allBtn) {
                allBtn.classList.add('active');
                var i = document.createElement('i');
                i.className = 'bi bi-check2 dropdown-check';
                allBtn.appendChild(i);
            }
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

    /* ⌘K / Ctrl+K */
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && String(e.key).toLowerCase() === 'k') {
            e.preventDefault();
            if (filterSearch) filterSearch.focus();
        }
    });

    render();
})();
</script>

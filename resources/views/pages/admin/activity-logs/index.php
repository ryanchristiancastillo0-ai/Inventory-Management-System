<?php
/**
 * ADMIN ACTIVITY LOGS PAGE
 * Controller: AdminActivityLogController::index()
 * Layout    : admin (topbar/sidebar provided by app.php)
 *
 * Composed from components/ + external CSS/JS.
 *
 * @var array $logs
 * @var array $stats
 * @var int   $currentPage
 * @var int   $totalPages
 * @var int   $totalLogs
 */

require __DIR__ . '/components/activity-logs-setup.php';
?>

<!-- Page styles (moved out of the old inline <style> block) -->
<link rel="stylesheet" href="<?= asset('/assets/css/admin/activity-logs.css?v=2026110902') ?>">

<main class="dashboard-content">
    <?php require __DIR__ . '/components/page-header.php'; ?>
    <?php require __DIR__ . '/components/stats-strip.php'; ?>
    <?php require __DIR__ . '/components/filter-bar.php'; ?>
    <?php require __DIR__ . '/components/logs-table.php'; ?>
</main>

<?php require __DIR__ . '/components/log-detail-modal.php'; ?>

<!-- ==========================================================
     Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<?php require __DIR__ . '/components/flash.php'; ?>

<script src="<?= asset('/assets/js/admin/activity-logs/activity-logs-ui.js?v=2026110902') ?>"></script>
<script src="<?= asset('/assets/js/admin/activity-logs/activity-logs-modals.js?v=2026110902') ?>"></script>
<script src="<?= asset('/assets/js/export.js?v=2026111101') ?>"></script>
<script src="<?= asset('/assets/js/admin/activity-logs/activity-logs-table.js?v=2026110904') ?>"></script>

<?php
/**
 * ADMIN REPORTS MANAGEMENT PAGE
 * Controller: AdminReportController::index() / ::update()
 * Layout    : admin (topbar/sidebar come from app.php)
 *
 * Composed from components/ + external CSS/JS.
 *
 * @var array       $reports
 * @var array       $stats
 * @var array|null  $flash       ['type' => 'success'|'danger', 'message' => string]
 * @var string      $csrfToken
 */

require __DIR__ . '/components/reports-setup.php';
?>

<!-- Page styles (moved out of the old inline <style> block) -->
<link rel="stylesheet" href="<?= asset('/assets/css/admin/reports.css?v=2026110902') ?>">

<main class="dashboard-content">
    <?php require __DIR__ . '/components/page-header.php'; ?>
    <?php require __DIR__ . '/components/stats-strip.php'; ?>
    <?php require __DIR__ . '/components/filter-bar.php'; ?>
    <?php require __DIR__ . '/components/reports-table.php'; ?>
</main>

<?php require __DIR__ . '/components/manage-report-modal.php'; ?>

<!-- ==========================================================
     Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<?php require __DIR__ . '/components/flash.php'; ?>

<script src="<?= asset('/assets/js/admin/reports/reports-ui.js?v=2026110902') ?>"></script>
<script src="<?= asset('/assets/js/admin/reports/reports-modals.js?v=2026110902') ?>"></script>
<script src="<?= asset('/assets/js/admin/reports/reports-table.js?v=2026110903') ?>"></script>

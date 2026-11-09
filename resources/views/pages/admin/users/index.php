<?php
/**
 * ADMIN USER MANAGEMENT PAGE
 * Rendered inside layouts/admin.php. Sidebar + topbar come from the layout.
 *
 * Composed from components/ + external CSS/JS.
 *
 * @var array       $users       List of user records
 * @var array       $stats       KPI map (auto-detected keys)
 * @var array|null  $flash       ['type' => 'success'|'danger', 'message' => string]
 * @var string      $csrfToken
 */

require __DIR__ . '/components/users-setup.php';
?>

<!-- Page styles (moved out of the old inline <style> block) -->
<link rel="stylesheet" href="<?= asset('/assets/css/admin/users.css?v=2026110903') ?>">

<main class="dashboard-content">
    <?php require __DIR__ . '/components/page-header.php'; ?>
    <?php require __DIR__ . '/components/stats-strip.php'; ?>
    <?php require __DIR__ . '/components/filter-bar.php'; ?>
    <?php require __DIR__ . '/components/users-table.php'; ?>
</main>

<?php require __DIR__ . '/components/new-user-modal.php'; ?>
<?php require __DIR__ . '/components/edit-user-modal.php'; ?>
<?php require __DIR__ . '/components/user-status-modal.php'; ?>

<!-- ==========================================================
     Toast stack (page-owned)
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<?php require __DIR__ . '/components/flash.php'; ?>

<script src="<?= asset('/assets/js/admin/users/users-ui.js?v=2026110801') ?>"></script>
<script src="<?= asset('/assets/js/admin/users/users-modals.js?v=2026110903') ?>"></script>
<script src="<?= asset('/assets/js/admin/users/users-table.js?v=2026110903') ?>"></script>

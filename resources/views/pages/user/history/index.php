<?php
/**
 * ORDER HISTORY PAGE
 * Rendered inside app.php. Sidebar and shell come from the layout.
 *
 * Composed from components/ + external CSS/JS.
 *
 * Expected variables (all optional — falls back to empty defaults):
 *   @var array       $history
 *   @var int         $totalTransactions, $createdCount, $updatedCount,
 *                    $completedCount, $cancelledCount
 *   @var string      $actionFilter        CREATED|UPDATED|COMPLETED|CANCELLED|''
 *   @var int|null    $selectedOrderId
 *   @var string      $csrfToken
 */

require __DIR__ . '/components/history-setup.php';
?>

<!-- Page styles (moved out of the old inline <style> block) -->
<link rel="stylesheet" href="/assets/css/user/history.css?v=2026110801">

<?php require __DIR__ . '/components/topbar.php'; ?>

<main class="dashboard-content">
    <?php require __DIR__ . '/components/page-header.php'; ?>
    <?php require __DIR__ . '/components/filter-bar.php'; ?>
    <?php require __DIR__ . '/components/history-table.php'; ?>
</main>

<!-- ==========================================================
     COMPONENT: Toast stack
     ========================================================== -->
<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

<?php require __DIR__ . '/components/flash.php'; ?>

<script src="/assets/js/user/history/history-ui.js?v=2026110801"></script>
<script src="/assets/js/user/history/history-table.js?v=2026110801"></script>

<?php
/**
 * ADMIN DASHBOARD PAGE
 * Rendered inside layouts/admin.php. Sidebar + topbar come from the layout.
 *
 * Composed from components/ + external CSS/JS.
 *
 * @var array $stats          KPI map (defensive — key names auto-detected)
 * @var array $registrations  Time series: [{date,count}|{label,value}|"Y-m-d"=>N]
 * @var array $recentUsers    [{id,name,email,role,status,created_at,...}, ...]
 */

require __DIR__ . '/components/dashboard-setup.php';
?>

<!-- Page styles (moved out of the old inline <style> block) -->
<link rel="stylesheet" href="<?= asset('/assets/css/admin/dashboard.css?v=2026110801') ?>">

<main class="dashboard-content">
    <?php require __DIR__ . '/components/page-header.php'; ?>
    <?php require __DIR__ . '/components/stats-strip.php'; ?>
    <?php require __DIR__ . '/components/premium-grid.php'; ?>
    <?php require __DIR__ . '/components/filter-bar.php'; ?>
    <?php require __DIR__ . '/components/recent-users-table.php'; ?>
</main>

<?php require __DIR__ . '/components/flash.php'; ?>

<script src="<?= asset('/assets/js/admin/dashboard/dashboard-users.js?v=2026110903') ?>"></script>

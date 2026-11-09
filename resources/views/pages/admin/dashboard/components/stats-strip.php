<!-- ==========================================================
     COMPONENT: Stats strip (5 KPIs)
     ========================================================== -->
<section class="stats-strip" aria-label="Dashboard statistics">
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-users"></span>
            <p class="stat-label">Total users</p>
        </div>
        <p class="stat-value"><?= number_format($totalUsers) ?></p>
        <p class="stat-hint"><?= number_format($activeUsers) ?> active</p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-active"></span>
            <p class="stat-label">Active users</p>
        </div>
        <p class="stat-value"><?= number_format($activeUsers) ?></p>
        <p class="stat-hint <?= $activePct >= 50 ? 'is-up' : 'is-down' ?>">
            <?= number_format($activePct, 1) ?>% of total
        </p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-new"></span>
            <p class="stat-label">New registrations</p>
        </div>
        <p class="stat-value"><?= number_format($newUsers) ?></p>
        <p class="stat-hint <?= $growthPct > 0 ? 'is-up' : '' ?>">
            +<?= number_format($growthPct, 1) ?>% growth
        </p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-pending"></span>
            <p class="stat-label">Pending orders</p>
        </div>
        <p class="stat-value"><?= number_format($pendingOrders) ?></p>
        <p class="stat-hint"><?= number_format($totalOrders) ?> total orders</p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-revenue"></span>
            <p class="stat-label">Revenue</p>
        </div>
        <p class="stat-value">₱<?= number_format($revenue, 2) ?></p>
        <p class="stat-hint">
            <?= $totalProducts > 0 ? number_format($totalProducts) . ' products · ' . number_format($lowStock) . ' low stock' : 'All-time total' ?>
        </p>
    </div>
</section>

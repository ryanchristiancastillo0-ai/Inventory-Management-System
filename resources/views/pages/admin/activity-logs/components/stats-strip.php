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

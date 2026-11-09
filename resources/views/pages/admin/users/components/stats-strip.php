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

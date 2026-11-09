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
            <p class="stat-hint">Closed &amp; archived</p>
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

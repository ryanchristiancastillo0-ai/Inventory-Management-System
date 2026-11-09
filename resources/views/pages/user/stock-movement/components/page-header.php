<!-- ==========================================================
     COMPONENT: Page header
     ========================================================== -->
<section class="page-header" aria-labelledby="pageTitle">
    <div class="min-w-0">
        <h1 class="page-title" id="pageTitle">Stock movements</h1>
        <p class="page-subtitle">
            Every in / out / adjustment recorded against your inventory
            <span class="today-badge">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                <?= number_format($todayCount) ?> today
            </span>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn-primary-app" id="addMovementBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>New movement
        </button>
    </div>
</section>

<!-- ==========================================================
     COMPONENT: Stats strip
     ========================================================== -->
<section class="stats-strip" aria-label="Movement statistics">
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot"></span>
            <p class="stat-label">Total movements</p>
        </div>
        <p class="stat-value"><?= number_format($totalMovements) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-in"></span>
            <p class="stat-label">Stock in</p>
        </div>
        <p class="stat-value is-in"><?= number_format($stockInCount) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-out"></span>
            <p class="stat-label">Stock out</p>
        </div>
        <p class="stat-value is-out"><?= number_format($stockOutCount) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-adjustment"></span>
            <p class="stat-label">Adjustments</p>
        </div>
        <p class="stat-value is-adjustment"><?= number_format($adjustmentCount) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-today"></span>
            <p class="stat-label">Today</p>
        </div>
        <p class="stat-value is-today"><?= number_format($todayCount) ?></p>
    </div>
</section>

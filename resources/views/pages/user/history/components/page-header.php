<!-- ==========================================================
     COMPONENT: Page header
     ========================================================== -->
<section class="page-header" aria-labelledby="pageTitle">
    <div class="min-w-0">
        <h1 class="page-title" id="pageTitle">Order history</h1>
        <p class="page-subtitle">
            Full audit trail of every order change
            <?php if ($selectedOrderId !== null): ?>
                <a class="filter-chip" href="<?= $e($buildUrl(['order_id' => null])) ?>" title="Clear order filter">
                    <i class="bi bi-receipt" aria-hidden="true"></i>
                    Order #<?= (int) $selectedOrderId ?>
                    <span class="chip-x" aria-hidden="true"><i class="bi bi-x" aria-hidden="true"></i></span>
                </a>
            <?php endif; ?>
            <?php if ($actionFilter !== ''): ?>
                <a class="filter-chip" href="<?= $e($buildUrl(['action' => null])) ?>" title="Clear action filter">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    <?= $e($actionFilterLabel) ?>
                    <span class="chip-x" aria-hidden="true"><i class="bi bi-x" aria-hidden="true"></i></span>
                </a>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/orders" class="btn-secondary">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>Back to orders
        </a>
    </div>
</section>

<!-- ==========================================================
     COMPONENT: Stats strip
     ========================================================== -->
<section class="stats-strip" aria-label="Order history statistics">
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot"></span>
            <p class="stat-label">Total transactions</p>
        </div>
        <p class="stat-value"><?= number_format($totalTransactions) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-created"></span>
            <p class="stat-label">Created</p>
        </div>
        <p class="stat-value is-created"><?= number_format($createdCount) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-updated"></span>
            <p class="stat-label">Updated</p>
        </div>
        <p class="stat-value is-updated"><?= number_format($updatedCount) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-completed"></span>
            <p class="stat-label">Completed</p>
        </div>
        <p class="stat-value is-completed"><?= number_format($completedCount) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-cancelled"></span>
            <p class="stat-label">Cancelled</p>
        </div>
        <p class="stat-value is-cancelled"><?= number_format($cancelledCount) ?></p>
    </div>
</section>

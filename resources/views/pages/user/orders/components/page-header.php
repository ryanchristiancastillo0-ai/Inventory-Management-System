<!-- ==========================================================
     COMPONENT: Page header
     ========================================================== -->
<section class="page-header" aria-labelledby="pageTitle">
    <div class="min-w-0">
        <h1 class="page-title" id="pageTitle">Restock orders</h1>
        <p class="page-subtitle">
            Track restocking orders across suppliers
            <span class="cost-badge">
                <i class="bi bi-cash-stack" aria-hidden="true"></i>
                ₱<?= number_format((float) $totalRestockCost, 2) ?> received
            </span>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn-secondary" id="manageSuppliersBtn">
            <i class="bi bi-people" aria-hidden="true"></i>Suppliers
        </button>
        <button type="button" class="btn-primary-app" id="addOrderBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>New order
        </button>
    </div>
</section>

<!-- ==========================================================
     COMPONENT: Stats strip
     ========================================================== -->
<section class="stats-strip" aria-label="Order statistics">
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot"></span>
            <p class="stat-label">Total orders</p>
        </div>
        <p class="stat-value"><?= number_format($totalOrders) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-pending"></span>
            <p class="stat-label">Pending</p>
        </div>
        <p class="stat-value is-pending"><?= number_format($pendingOrders) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-ordered"></span>
            <p class="stat-label">Ordered</p>
        </div>
        <p class="stat-value is-ordered"><?= number_format($orderedOrders) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-received"></span>
            <p class="stat-label">Received</p>
        </div>
        <p class="stat-value is-received"><?= number_format($receivedOrders) ?></p>
    </div>
    <div class="stat-item">
        <div class="stat-top">
            <span class="stat-dot is-cancelled"></span>
            <p class="stat-label">Cancelled</p>
        </div>
        <p class="stat-value is-cancelled"><?= number_format($cancelledOrders) ?></p>
    </div>
</section>

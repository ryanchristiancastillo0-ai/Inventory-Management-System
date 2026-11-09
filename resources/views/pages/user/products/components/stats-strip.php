<!-- ==========================================================
     COMPONENT: Stats strip (server-computed)
     ========================================================== -->
<section class="stats-strip" aria-label="Product statistics">
    <div class="stat-item">
        <p class="stat-label">Total products</p>
        <p class="stat-value"><?= number_format($totalProducts) ?></p>
    </div>
    <div class="stat-item">
        <p class="stat-label">In stock</p>
        <p class="stat-value"><?= number_format($inStock) ?></p>
    </div>
    <div class="stat-item">
        <p class="stat-label">Low stock</p>
        <p class="stat-value<?= $lowStock > 0 ? ' is-low' : '' ?>"><?= number_format($lowStock) ?></p>
    </div>
    <div class="stat-item">
        <p class="stat-label">Out of stock</p>
        <p class="stat-value<?= $outOfStock > 0 ? ' is-out' : '' ?>"><?= number_format($outOfStock) ?></p>
    </div>
</section>

<!-- ==========================================================
     Page header
     ========================================================== -->
<section class="page-header" aria-labelledby="pageTitle">
    <div class="min-w-0">
        <h1 class="page-title" id="pageTitle">Activity logs</h1>
        <p class="page-subtitle">
            Every action taken across your workspace
            <span class="date-chip">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                Page <?= $currentPage ?> of <?= $totalPages ?> · <?= number_format($totalLogs) ?> total
            </span>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn-secondary" id="exportCsvBtn">
            <i class="bi bi-download" aria-hidden="true"></i>Export
        </button>
    </div>
</section>

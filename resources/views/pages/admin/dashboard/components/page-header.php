<!-- ==========================================================
     COMPONENT: Page header
     ========================================================== -->
<section class="page-header" aria-labelledby="pageTitle">
    <div class="min-w-0">
        <h1 class="page-title" id="pageTitle">Welcome back, Administrator</h1>
        <p class="page-subtitle">
            Here's what's happening across your store today
            <span class="date-chip">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                <?= date('l, F j, Y') ?>
            </span>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="/admin/users" class="btn-secondary">
            <i class="bi bi-people" aria-hidden="true"></i>Manage users
        </a>
        <a href="/admin/activity-logs" class="btn-primary-app">
            <i class="bi bi-activity" aria-hidden="true"></i>Activity logs
        </a>
    </div>
</section>

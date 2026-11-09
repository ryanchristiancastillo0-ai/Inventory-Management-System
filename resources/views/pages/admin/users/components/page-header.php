<!-- ==========================================================
     Page header
     ========================================================== -->
<section class="page-header" aria-labelledby="pageTitle">
    <div class="min-w-0">
        <h1 class="page-title" id="pageTitle">User management</h1>
        <p class="page-subtitle">
            Manage admins and regular users across your workspace
            <span class="date-chip">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                <?= date('l, F j, Y') ?>
            </span>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn-primary-app" data-bs-toggle="modal" data-bs-target="#newUserModal">
            <i class="bi bi-person-plus" aria-hidden="true"></i>Add user
        </button>
    </div>
</section>

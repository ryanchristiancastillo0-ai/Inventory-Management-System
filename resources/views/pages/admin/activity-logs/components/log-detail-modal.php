<!-- ==========================================================
     MODAL: Log detail
     ========================================================== -->
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modal-content-app">
            <div class="modal-header-app">
                <div>
                    <h2 class="modal-title-app" id="logDetailTitle">Log detail</h2>
                    <p class="modal-subtitle-app">Full record of this activity</p>
                </div>
                <button type="button" class="icon-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-body-app">
                <div class="detail-grid">
                    <p class="detail-label">Log ID</p>
                    <p class="detail-value is-mono" id="d-id">—</p>

                    <p class="detail-label">Action</p>
                    <p class="detail-value" id="d-action">—</p>

                    <p class="detail-label">Actor</p>
                    <p class="detail-value" id="d-actor">—</p>

                    <p class="detail-label">Email</p>
                    <p class="detail-value is-muted" id="d-email">—</p>

                    <p class="detail-label">Target</p>
                    <p class="detail-value" id="d-target">—</p>



                    <p class="detail-label">When</p>
                    <p class="detail-value" id="d-when">—</p>

                    <p class="detail-label">Details</p>
                    <p class="detail-value is-muted is-block" id="d-details" style="grid-column:2">—</p>
                </div>
            </div>

            <div class="modal-footer-app">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

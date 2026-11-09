<?php
/**
 * COMPONENT: Flash message toasts (session-driven, inline script)
 * plus the current page number for the page's export JS
 * (dynamic PHP values must stay inline PHP).
 * Depends on the page-owned window.showToast() (activity-logs-ui.js).
 */
?>
<script>window.__LOGS_PAGE = <?= (int) $currentPage ?>;</script>

<?php if ($flashSuccess !== null && $flashSuccess !== ''): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.showToast(<?= json_encode($flashSuccess) ?>, 'bi-check2-circle');
        });
    </script>
<?php endif; ?>
<?php if ($flashError !== null && $flashError !== ''): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.showToast(<?= json_encode($flashError) ?>, 'bi-exclamation-circle', true);
        });
    </script>
<?php endif; ?>

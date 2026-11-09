<?php
/**
 * COMPONENT: Flash message toasts (session-driven, inline script).
 * Must stay inline PHP because the messages are dynamic.
 * Depends on window.showToast() from the admin topbar component.
 */
?>
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
            window.showToast(<?= json_encode($flashError) ?>, 'bi-exclamation-circle');
        });
    </script>
<?php endif; ?>

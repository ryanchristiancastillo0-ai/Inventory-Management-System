<?php
/**
 * COMPONENT: Flash message toast (session-driven, inline script).
 * Must stay inline PHP because the messages are dynamic.
 * Depends on the page-owned window.showToast() (users-ui.js).
 */
?>
<?php if ($flashMsg !== ''): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.showToast(
                <?= json_encode($flashMsg) ?>,
                <?= json_encode($flashType === 'danger' ? 'bi-exclamation-circle' : 'bi-check2-circle') ?>,
                <?= $flashType === 'danger' ? 'true' : 'false' ?>
            );
        });
    </script>
<?php endif; ?>

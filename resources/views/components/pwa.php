<?php
/**
 * PWA wiring: manifest, icons, theme color and service worker registration.
 * Shared by all page layouts — include once inside <head>.
 *
 * Note: service workers and the install prompt only work over HTTPS
 * (or on localhost). Everywhere else this degrades gracefully.
 */
?>
<!-- PWA -->
<link rel="manifest" href="<?= asset('/manifest.webmanifest') ?>">
<meta name="theme-color" content="#0B0B0C">
<link rel="icon" type="image/png" sizes="32x32" href="<?= asset('/assets/icons/favicon-32.png') ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= asset('/assets/icons/favicon-16.png') ?>">
<link rel="apple-touch-icon" href="<?= asset('/assets/icons/apple-touch-icon.png') ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="Inventory">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker
                .register('<?= asset('/sw.js') ?>')
                .catch(function () {
                    // Best-effort: needs HTTPS (or localhost) to register.
                });
        });
    }
</script>

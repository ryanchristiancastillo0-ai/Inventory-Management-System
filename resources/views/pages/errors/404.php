<?php
/**
 * errors/404.php — themed "page not found" screen.
 *
 * Uses the shared design tokens (main.css + theme.css) so the page
 * follows the SYSTEM / LIGHT / DARK preference of the signed-in user.
 *
 * @var string $path      The requested path that could not be matched.
 * @var string $homeUrl   Where the primary action should send the user.
 * @var string $homeLabel Label for the primary action.
 */

$path      = (string) ($path ?? '/');
$homeUrl   = (string) ($homeUrl ?? '/');
$homeLabel = (string) ($homeLabel ?? 'Go to home');

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<main class="error-page">

    <section class="error-card" aria-labelledby="error-title">

        <a class="error-brand" href="<?= $e($homeUrl) ?>">
            <span class="error-brand-mark" aria-hidden="true">I</span>
            <span class="error-brand-text">Inventory.</span>
        </a>

        <p class="error-code" aria-hidden="true">
            <span>4</span><span class="error-code-muted">0</span><span>4</span>
        </p>

        <span class="error-eyebrow">
            <i class="bi bi-sign-dead-end" aria-hidden="true"></i>
            Route not found
        </span>

        <h1 class="error-title" id="error-title">We couldn't find that page</h1>

        <p class="error-text">
            The address you followed doesn't match anything here. It may have
            been moved, renamed, or typed incorrectly.
        </p>

        <p class="error-path" title="Requested path">
            <i class="bi bi-link-45deg" aria-hidden="true"></i>
            <code><?= $e($path) ?></code>
        </p>

        <div class="error-actions">
            <a class="btn-primary-app" href="<?= $e($homeUrl) ?>">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <?= $e($homeLabel) ?>
            </a>

            <button type="button" class="btn-secondary" data-error-back>
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                Go back
            </button>
        </div>

    </section>

    <p class="error-footer">
        <span>Error 404</span>
        <span class="error-footer-dot" aria-hidden="true"></span>
        <span>Inventory Management System</span>
    </p>

</main>

<script>
    (function () {
        var button = document.querySelector('[data-error-back]');
        if (!button) return;

        button.addEventListener('click', function () {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.assign('<?= $e($homeUrl) ?>');
            }
        });
    })();
</script>

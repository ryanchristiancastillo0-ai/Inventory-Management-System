<?php
/** @var string $pageContent */
/** @var string $title */
?>
<!DOCTYPE html>
<html lang="en" data-theme-preference="<?= htmlspecialchars($appThemePreference ?? 'SYSTEM', ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Inventory System') ?></title>
    <script src="<?= asset('/assets/js/theme.js?v=2026110707') ?>"></script>
    <link rel="stylesheet" href="<?= asset('/assets/css/main.css?v=2026110905') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/theme.css?v=2026110902') ?>">
   
    <!-- BOOTSTRAP JS (head so inline page scripts can use it) -->
    <script src="<?= asset('/assets/bootstrap/js/bootstrap.bundle.min.js?v=2026110707') ?>"></script>
    <script src="<?= asset('/assets/js/ui-overlays.js?v=2026110904') ?>"></script>
    <script src="<?= asset('/assets/js/account-navigation.js?v=2026110707') ?>" defer></script>
    <link rel="stylesheet" href="<?= asset('/assets/css/account-navigation.css?v=2026110707') ?>">
   
    <link rel="stylesheet" href="<?= asset('/assets/css/biometric.css?v=2026110707') ?>">
    <script src="<?= asset('/assets/js/biometric-modal.js?v=2026110707') ?>"></script>
    <link rel="stylesheet" href="<?= asset('/assets/bootstrap-icons/bootstrap-icons.min.css?v=2026110707') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-shell">
        <?php require __DIR__ . '/../components/user/sidebar.php' ?>

        <div class="dashboard-main dashboard-main-offset">
            <?= $pageContent ?>
        </div>
    </div>
</body>
</html>

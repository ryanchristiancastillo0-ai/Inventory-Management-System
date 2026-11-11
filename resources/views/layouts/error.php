<?php

declare(strict_types=1);

/** @var string $pageContent */
/** @var string $title */

?>
<!DOCTYPE html>
<html lang="en" data-theme-preference="<?= htmlspecialchars($appThemePreference ?? 'SYSTEM', ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="robots" content="noindex">

    <title><?= htmlspecialchars($title ?? 'Page not found · Inventory.') ?></title>

    <!-- THEME (must load before paint to avoid a flash of the wrong theme) -->
    <script src="<?= asset('/assets/js/theme.js?v=2026110707') ?>"></script>

    <!-- MAIN CSS -->
    <link rel="stylesheet" href="<?= asset('/assets/css/main.css?v=2026110905') ?>">
    <link rel="stylesheet" href="<?= asset('/assets/css/theme.css?v=2026110902') ?>">

    <!-- ERROR PAGE CSS -->
    <link rel="stylesheet" href="<?= asset('/assets/css/error.css?v=2026111101') ?>">

    <!-- BOOTSTRAP ICONS -->
    <link rel="stylesheet" href="<?= asset('/assets/bootstrap-icons/bootstrap-icons.min.css?v=2026110707') ?>">

    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php require __DIR__ . '/../components/pwa.php' ?>
</head>
<body class="error-body">

    <?= $pageContent ?>

</body>
</html>


<?php
/** @var string $pageContent */
/** @var string $title */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($title ?? 'Inventory System') ?>
    </title>

    <!-- MAIN CSS -->
    <link rel="stylesheet" href="<?= asset('/assets/css/main.css?v=2026110707') ?>">

    <!-- BOOTSTRAP JS (head so inline page scripts can use it) -->
    <script src="<?= asset('/assets/bootstrap/js/bootstrap.bundle.min.js?v=2026110707') ?>"></script>

    <!-- BOOTSTRAP ICONS -->
   <link rel="stylesheet" href="<?= asset('/assets/bootstrap-icons/bootstrap-icons.min.css?v=2026110707') ?>">

    <!-- BIOMETRIC VERIFICATION UI -->
    <link rel="stylesheet" href="<?= asset('/assets/css/biometric.css?v=2026110707') ?>">
    <script src="<?= asset('/assets/js/biometric-modal.js?v=2026110707') ?>"></script>

</head>
<body>

    <?= $pageContent ?>

</body>
</html>

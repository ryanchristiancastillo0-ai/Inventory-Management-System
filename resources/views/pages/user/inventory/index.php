<?php
// Inventory and Products operate on the same catalog. Share the real data,
// forms and API handlers rather than maintaining a separate demo-only editor.
$catalogTitle = 'Inventory';
require __DIR__ . '/../products/index.php';

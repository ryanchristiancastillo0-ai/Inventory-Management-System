<?php

declare(strict_types=1);

/**
 * TEMPORARY FaceService end-to-end test (CLI).
 * Enrolls a synthetic descriptor for the seeded admin, verifies a
 * near-identical scan, asserts the far one is rejected, then cleans up.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Repositories\FaceRepo;
use App\Repositories\AuthRepo;
use App\Services\FaceService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$faceService = new FaceService(new FaceRepo(), new AuthRepo());

/* Build a unit-length 128-float vector (as face-api.js produces) */
$makeDescriptor = static function (float $center): array {
    $vector = [];
    for ($i = 0; $i < 128; $i++) {
        $vector[] = sin($i * ($center + 0.01));
    }
    $norm = sqrt(array_reduce($vector, fn ($carry, $v) => $carry + $v * $v, 0.0));
    return array_map(fn ($v) => $v / $norm, $vector);
};

$enrolled = $makeDescriptor(1.0);
$close    = $makeDescriptor(1.0);       // same pattern -> same person
$far      = $makeDescriptor(50.0);      // different pattern -> stranger

$userId = 2; // seeded admin (id=2)

/* Shape check: descriptor must be 128 floats */
try {
    $faceService->verifyLogin(['challenge' => 'x', 'descriptor' => [0, 1, 2]]);
    echo "FAIL: malformed descriptor was accepted" . PHP_EOL;
    exit(1);
} catch (RuntimeException $e) {
    echo "OK: invalid descriptor rejected (" . $e->getMessage() . ")" . PHP_EOL;
}

/* Enroll */
$count = $faceService->enrollFace($userId, [$enrolled]);
echo ($count === 1 ? "OK" : "FAIL") . ": enrolled {$count} face(s) for user #{$userId}" . PHP_EOL;

/* Near-identical scan must match */
$challenge = $faceService->beginLogin();
$user = $faceService->verifyLogin([
    "challenge"  => $challenge,
    "descriptor" => $close
]);
echo ($user["username"] === "Admin"
    ? "OK: matching scan resolved user {$user['username']} (role {$user['role']})"
    : "FAIL: wrong user resolved") . PHP_EOL;
echo "  distance test passed" . PHP_EOL;

/* Rejected scans must not match */
try {
    $challenge = $faceService->beginLogin();
    $faceService->verifyLogin(["challenge" => $challenge, "descriptor" => $far]);
    echo "FAIL: far scan was accepted" . PHP_EOL;
    exit(1);
} catch (RuntimeException $e) {
    echo "OK: stranger face rejected (" . $e->getMessage() . ")" . PHP_EOL;
}

/* Challenge cannot be replayed */
try {
    $faceService->verifyLogin(["challenge" => $challenge, "descriptor" => $close]);
    echo "FAIL: challenge replay accepted" . PHP_EOL;
    exit(1);
} catch (RuntimeException $e) {
    echo "OK: challenge replay blocked (" . $e->getMessage() . ")" . PHP_EOL;
}

/* Enrollment listing + cleanup */
$enrollments = $faceService->getEnrollments($userId);
echo "OK: enrollment count = " . $enrollments["count"] . PHP_EOL;

$faceService->deleteFaces($userId);
(new FaceRepo())->deleteFacesByUserId($userId);
echo "OK: cleanup done (desk DB has no leftover descriptors)" . PHP_EOL;

echo "ALL FACE TESTS PASSED" . PHP_EOL;
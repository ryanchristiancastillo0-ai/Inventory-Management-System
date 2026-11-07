<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

$pdo = Database::connect();
$stmt = $pdo->prepare("SELECT id, username, email, password_hash, role, created_at FROM users WHERE email = :email");
$stmt->execute(["email" => "admin@gmail.com"]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "NOT FOUND" . PHP_EOL;
    exit(1);
}

echo "id: " . $user["id"] . PHP_EOL;
echo "username: " . $user["username"] . PHP_EOL;
echo "email: " . $user["email"] . PHP_EOL;
echo "role: " . $user["role"] . PHP_EOL;
echo "created_at: " . $user["created_at"] . PHP_EOL;
echo password_verify("12345678", $user["password_hash"])
    ? "PASSWORD 12345678: OK (hash stored correctly)" . PHP_EOL
    : "PASSWORD 12345678: FAILED" . PHP_EOL;

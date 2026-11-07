<?php

declare(strict_types=1);

/**
 * ADMIN SEEDER
 * Creates (or upgrades) an admin account:
 *   Email    : admin@gmail.com
 *   Password : 12345678
 *
 * Run from the project root:  php database\seed_admin.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

const SEED_EMAIL    = 'admin@gmail.com';
const SEED_USERNAME = 'Admin';
const SEED_PASSWORD = '12345678';

try {
    $pdo = Database::connect();
} catch (Throwable $error) {
    fwrite(STDERR, "Database connection failed: " . $error->getMessage() . PHP_EOL);
    exit(1);
}

// The password is always stored hashed — matching AuthService/AdminUserService.
$hash = password_hash(SEED_PASSWORD, PASSWORD_DEFAULT);

// 1. Does the email already exist?
$lookup = $pdo->prepare("SELECT id, role FROM users WHERE email = :email");
$lookup->execute(["email" => SEED_EMAIL]);
$existing = $lookup->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // 2a. Promote to ADMIN / reset password.
    $update = $pdo->prepare(
        "UPDATE users
         SET password_hash = :hash,
             role = 'ADMIN'
         WHERE id = :id"
    );
    $update->execute(["hash" => $hash, "id" => (int) $existing["id"]]);

    echo "Existing user #" . $existing["id"] .
         " updated -> role ADMIN, password reset for " . SEED_EMAIL . PHP_EOL;
    exit(0);
}

// 2b. Username taken by someone else? Uniquely suffix it.
$username = SEED_USERNAME;
$suffix   = 0;

$nameCheck = $pdo->prepare("SELECT id FROM users WHERE username = :username");
$nameCheck->execute(["username" => $username]);

while ($nameCheck->fetch(PDO::FETCH_ASSOC) !== false) {
    $suffix++;
    $username = SEED_USERNAME . $suffix;
    $nameCheck->execute(["username" => $username]);
}

// 3. Insert the admin account.
$insert = $pdo->prepare(
    "INSERT INTO users
        (username, email, password_hash, role, created_at)
     VALUES
        (:username, :email, :hash, 'ADMIN', NOW())"
);
$insert->execute([
    "username" => $username,
    "email"    => SEED_EMAIL,
    "hash"     => $hash,
]);

$adminId = (int) $pdo->lastInsertId();
echo "Admin created -> id=$adminId, username=$username, email=" . SEED_EMAIL . PHP_EOL;
echo "Login with email " . SEED_EMAIL . " and password " . SEED_PASSWORD . " (change it after first login)." . PHP_EOL;

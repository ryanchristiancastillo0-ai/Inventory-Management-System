<?php

declare(strict_types=1);

/**
 * USER_FACES TABLE MIGRATION
 * Adds the table used by FaceService/FaceRepo for face-login enrollment.
 *
 * Run from the project root:  php database\migrate_user_faces.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

try {
    $pdo = Database::connect();

    // Read the exact integer type of users.id so the foreign key matches.
    $typeCheck = $pdo->prepare(
        "SELECT COLUMN_TYPE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'users'
           AND COLUMN_NAME = 'id'"
    );
    $typeCheck->execute();
    $idType = (string) ($typeCheck->fetchColumn() ?: 'INT UNSIGNED');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS user_faces (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id      {$idType} NOT NULL,
            descriptor   TEXT NOT NULL,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_faces_user
                FOREIGN KEY (user_id) REFERENCES users (id)
                ON DELETE CASCADE,
            INDEX idx_user_faces_user (user_id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    echo "user_faces table ready." . PHP_EOL;
    exit(0);

} catch (Throwable $error) {
    fwrite(STDERR, "Migration failed: " . $error->getMessage() . PHP_EOL);
    exit(1);
}
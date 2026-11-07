<?php

declare(strict_types=1);

namespace App\Config;

use PDO;

class Database
{
    public static function connect(): PDO
    {
        return new PDO(
            "mysql:host=localhost;dbname=inventory_db;charset=utf8mb4",
            "root",
            "",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }
}
<?php

declare(strict_types=1);

namespace App\Config;

use PDO;

class Database
{
    // ==========================================
    // DATABASE CONNECTION SETTINGS
    // Real credentials live in the ".env" file
    // in the project root. Never change them
    // here — edit .env instead.
    // ==========================================

    /** @var array<string, string>|null Cache of parsed .env values */
    private static ?array $env = null;

    public static function connect(): PDO
    {
        return new PDO(
            "mysql:host=" . self::env('DB_HOST')
                . ";dbname=" . self::env('DB_NAME')
                . ";charset=" . self::env('DB_CHARSET'),
            self::env('DB_USER'),
            self::env('DB_PASS'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }

    // ==========================================
    // .ENV LOADER
    // Reads the .env file once and caches it.
    // Supports: KEY=value lines, # comments,
    // quoted values, and blank lines.
    // ==========================================

    private static function env(string $key): string
    {
        self::loadEnv();

        return self::$env[$key] ?? '';
    }

    private static function loadEnv(): void
    {
        if (self::$env !== null) {
            return; // already loaded
        }

        self::$env = [];

        $envFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';

        if (!is_file($envFile)) {
            error_log('[Database] .env file not found at: ' . $envFile);
            return;
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $trimmed = trim($line);

            // Skip comments and invalid lines
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $separatorPos = strpos($trimmed, '=');

            if ($separatorPos === false) {
                continue;
            }

            $name  = trim(substr($trimmed, 0, $separatorPos));
            $value = trim(substr($trimmed, $separatorPos + 1));

            // Strip surrounding quotes if present
            if (
                strlen($value) >= 2
                && ($value[0] === '"' || $value[0] === "'")
                && $value[0] === substr($value, -1)
            ) {
                $value = substr($value, 1, -1);
            }

            self::$env[$name] = $value;
        }
    }
}
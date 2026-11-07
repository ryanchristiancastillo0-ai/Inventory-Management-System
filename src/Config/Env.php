<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

/**
 * Lightweight .env loader.
 *
 * Reads KEY=VALUE pairs from the project-root .env file once per
 * request and exposes them through Env::get(). Values already set
 * in the real environment always win.
 */
class Env
{
    /** @var array<string, string>|null */
    private static ?array $values = null;

    public static function load(?string $path = null): void
    {
        if (self::$values !== null) {
            return;
        }

        $path ??= dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . ".env";

        self::$values = [];

        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === "" || str_starts_with($line, "#")) {
                continue;
            }

            $separator = strpos($line, "=");

            if ($separator === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separator));
            $value = trim(substr($line, $separator + 1));

            // Strip wrapping single/double quotes.
            if (
                strlen($value) >= 2 &&
                (
                    str_starts_with($value, '"') && str_ends_with($value, '"') ||
                    str_starts_with($value, "'") && str_ends_with($value, "'")
                )
            ) {
                $value = substr($value, 1, -1);
            }

            if ($key !== "") {
                self::$values[$key] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::load();

        $real = getenv($key);

        if ($real !== false && $real !== null && $real !== "") {
            return $real;
        }

        return self::$values[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        $value = strtolower(trim($value));

        return in_array($value, ["1", "true", "yes", "on"], true);
    }

    /**
     * @throws RuntimeException when a required variable is missing.
     */
    public static function require(string $key): string
    {
        $value = self::get($key);

        if ($value === null || trim($value) === "") {
            throw new RuntimeException(
                "Environment variable \"{$key}\" is not set."
            );
        }

        return $value;
    }
}

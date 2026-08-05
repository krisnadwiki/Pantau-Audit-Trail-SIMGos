<?php
/**
 * Environment File Parser
 * Membaca file .env tanpa framework/Composer
 */

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip komentar
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // Pastikan ada tanda =
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key   = trim($key);
        $value = trim($value);

        // Hapus tanda kutip jika ada
        $value = trim($value, '"\'');

        putenv("{$key}={$value}");
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
    }
}

/**
 * Ambil nilai environment variable
 */
function env(string $key, $default = null)
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    $lower = strtolower($value);
    if ($lower === 'true' || $lower === '(true)') return true;
    if ($lower === 'false' || $lower === '(false)') return false;
    if ($lower === 'null' || $lower === '(null)') return null;

    return $value;
}

<?php
// app/core/Env.php
#[\AllowDynamicProperties]
class Env {
    private static array $vars = [];

    public static function load(string $path): void {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");
            self::$vars[$key] = $value;
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }

    public static function get(string $key, $default=null) {
        return self::$vars[$key] ?? $_ENV[$key] ?? getenv($key) ?? $default;
    }
}

// Global helper for convenience (like Laravel's env())
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return Env::get($key, $default);
    }
}
<?php
// app/helpers/url_helper.php
function base_url(string $path = ''): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $base = $scheme . '://' . $host . $scriptDir;
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function redirect(string $to, int $code=302): void {
    header('Location: ' . (preg_match('#^https?://#', $to) ? $to : base_url($to)), true, $code);
    exit;
}
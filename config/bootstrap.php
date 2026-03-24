<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function app_config(): array
{
    static $config;

    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }

    return $config;
}

function app_url(string $path = ''): string
{
    $base = rtrim((string) app_config()['app']['base_url'], '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base : $base . '/' . $path;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function sanitize_rich_text(string $html): string
{
    $allowedTags = '<p><br><b><strong><i><em><u><ul><ol><li><a><blockquote><code><pre><span>';
    $sanitized = strip_tags($html, $allowedTags);

    // Prevent javascript: URLs
    $sanitized = preg_replace('/href\s*=\s*"\s*javascript:[^"]*"/i', 'href="#"', $sanitized ?? '');
    $sanitized = preg_replace('/href\s*=\s*\'\s*javascript:[^\']*\'/i', "href='#'", $sanitized ?? '');

    return trim((string) $sanitized);
}

function format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    $units = ['KB', 'MB', 'GB'];
    $value = $bytes / 1024;
    $unitIndex = 0;

    while ($value >= 1024 && $unitIndex < count($units) - 1) {
        $value /= 1024;
        $unitIndex++;
    }

    return number_format($value, 2) . ' ' . $units[$unitIndex];
}

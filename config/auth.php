<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function app_base_url(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

    if (preg_match('#/(admin|teacher|student|api|qr)$#', $scriptDir)) {
        $scriptDir = str_replace('\\', '/', dirname($scriptDir));
    }

    if ($scriptDir === '/' || $scriptDir === '.') {
        return '';
    }

    return rtrim($scriptDir, '/');
}

function app_url(string $path = ''): string
{
    $base = app_base_url();
    $path = ltrim($path, '/');

    return $path === '' ? ($base === '' ? '/' : $base . '/') : ($base === '' ? '/' . $path : $base . '/' . $path);
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_auth(?string $role = null): void
{
    if (!isset($_SESSION['user'])) {
        header('Location: ' . app_url('login.php'));
        exit;
    }

    if ($role !== null && ($_SESSION['user']['role'] ?? null) !== $role) {
        http_response_code(403);
        exit('Unauthorized');
    }
}

<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
        header('Location: /login.php');
        exit;
    }

    if ($role !== null && ($_SESSION['user']['role'] ?? null) !== $role) {
        http_response_code(403);
        exit('Unauthorized');
    }
}

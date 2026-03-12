<?php

declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'] ?? $user['username'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];
}

function require_role(array $roles): void
{
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        header('Location: /login.php');
        exit;
    }
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

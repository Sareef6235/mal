<?php
namespace App\Helpers;

use RuntimeException;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function verify(string $token): void
    {
        if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
            throw new RuntimeException('Invalid CSRF token.');
        }
    }
}

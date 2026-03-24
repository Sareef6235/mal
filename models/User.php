<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class User
{
    public static function findByUsername(string $username): ?array
    {
        $sql = 'SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}

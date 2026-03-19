<?php
namespace App\Helpers;

use App\Services\Database;
use PDO;
use RuntimeException;

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::user()) {
            header('Location: index.php#auth');
            exit;
        }
    }

    public static function register(string $name, string $email, string $password): void
    {
        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException('All registration fields are required.');
        }

        $pdo = Database::connection();
        $statement = $pdo->prepare('INSERT INTO users (name, email, password_hash, plan, created_at) VALUES (:name, :email, :password_hash, :plan, NOW())');
        $statement->execute([
            ':name' => $name,
            ':email' => strtolower($email),
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':plan' => 'starter',
        ]);

        $_SESSION['user'] = [
            'id' => (int) $pdo->lastInsertId(),
            'name' => $name,
            'email' => strtolower($email),
            'plan' => 'starter',
        ];
    }

    public static function attempt(string $email, string $password): void
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute([':email' => strtolower($email)]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Invalid login credentials.');
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'plan' => $user['plan'],
        ];
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/security.php';

function findUserByUsername(string $username): ?array
{
    $stmt = db()->prepare('SELECT * FROM ustads WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function findUserById(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM ustads WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function loginAttemptsKey(string $username): string
{
    return 'login_' . strtolower($username);
}

function otpAttemptsKey(string $username): string
{
    return 'otp_' . strtolower($username);
}

function isLockedOut(string $username): bool
{
    startSecureSession();
    $key = loginAttemptsKey($username);
    $entry = $_SESSION[$key] ?? null;
    if (!$entry) {
        return false;
    }

    if (($entry['count'] ?? 0) < LOGIN_MAX_ATTEMPTS) {
        return false;
    }

    $lockedUntil = (int) ($entry['locked_until'] ?? 0);
    if (time() > $lockedUntil) {
        unset($_SESSION[$key]);
        return false;
    }

    return true;
}

function recordLoginFailure(string $username): void
{
    startSecureSession();
    $key = loginAttemptsKey($username);
    $entry = $_SESSION[$key] ?? ['count' => 0, 'locked_until' => 0];
    $entry['count']++;

    if ($entry['count'] >= LOGIN_MAX_ATTEMPTS) {
        $entry['locked_until'] = time() + (LOGIN_LOCK_MINUTES * 60);
    }

    $_SESSION[$key] = $entry;
}

function clearLoginFailures(string $username): void
{
    startSecureSession();
    unset($_SESSION[loginAttemptsKey($username)]);
}

function setRememberToken(int $userId): void
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);

    $stmt = db()->prepare('UPDATE ustads SET remember_token = ? WHERE id = ?');
    $stmt->execute([$hash, $userId]);

    setcookie(
        REMEMBER_COOKIE_NAME,
        $token,
        [
            'expires' => time() + (REMEMBER_COOKIE_DAYS * 86400),
            'path' => '/',
            'httponly' => true,
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ]
    );
}

function attemptRememberLogin(): void
{
    startSecureSession();
    if (isset($_SESSION['user_id']) || empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        return;
    }

    $hash = hash('sha256', (string) $_COOKIE[REMEMBER_COOKIE_NAME]);
    $stmt = db()->prepare('SELECT * FROM ustads WHERE remember_token = ? LIMIT 1');
    $stmt->execute([$hash]);
    $user = $stmt->fetch();
    if (!$user) {
        return;
    }

    completeLogin($user, false);
}

function completeLogin(array $user, bool $rememberMe): void
{
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    $stmt = db()->prepare('UPDATE ustads SET last_login = NOW() WHERE id = ?');
    $stmt->execute([(int) $user['id']]);

    if ($rememberMe) {
        setRememberToken((int) $user['id']);
    }
}

function requireAuth(): array
{
    enforceSessionTimeout();
    attemptRememberLogin();

    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $user = findUserById((int) $_SESSION['user_id']);
    if (!$user) {
        logout();
        header('Location: login.php');
        exit;
    }

    if (empty($user['email']) && basename($_SERVER['PHP_SELF']) !== 'set_email.php') {
        header('Location: set_email.php');
        exit;
    }

    return $user;
}

function requireAdmin(): array
{
    $user = requireAuth();
    if (($user['role'] ?? 'user') !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
    return $user;
}

function validPasswordPolicy(string $password): bool
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/', $password);
}

function sendOtpEmail(string $toEmail, string $otp): void
{
    $subject = 'Your Password Reset OTP';
    $message = "Your OTP code is {$otp}. It expires in " . OTP_EXPIRY_MINUTES . ' minutes.';
    $headers = 'From: ' . APP_FROM_EMAIL;
    @mail($toEmail, $subject, $message, $headers);
}

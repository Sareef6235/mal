<?php
/**
 * Core bootstrap for the premium profile dashboard.
 * Configure these values through environment variables in production.
 */
declare(strict_types=1);

session_name('MAL_PROFILE_SESSION');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const APP_NAME = 'MAL Nexus';
const UPLOAD_DIR = __DIR__ . '/uploads';
const UPLOAD_URL = 'uploads';

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env_value('DB_HOST', '127.0.0.1');
    $name = env_value('DB_NAME', 'mal_dashboard');
    $user = env_value('DB_USER', 'root');
    $pass = env_value('DB_PASS', '');
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(['success' => false, 'message' => 'Security token expired. Refresh and try again.'], 419);
    }
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

function clean_string(?string $value, int $max = 255): string
{
    $value = trim((string) $value);
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    return mb_substr($value, 0, $max);
}

function require_auth(): int
{
    if (!empty($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    // Local demo fallback keeps the page previewable; disable by setting DEMO_AUTH=false.
    if (env_value('DEMO_AUTH', 'true') === 'true') {
        $_SESSION['user_id'] = 1;
        return 1;
    }

    header('Location: login.php');
    exit;
}

function rate_limit(string $key, int $limit, int $seconds): bool
{
    $now = time();
    $_SESSION['rate_limits'][$key] = array_filter(
        $_SESSION['rate_limits'][$key] ?? [],
        static fn (int $timestamp): bool => ($now - $timestamp) < $seconds
    );

    if (count($_SESSION['rate_limits'][$key]) >= $limit) {
        return false;
    }

    $_SESSION['rate_limits'][$key][] = $now;
    return true;
}

function get_user(int $userId): array
{
    $user = null;
    try {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
    } catch (Throwable $exception) {
        if (env_value('DEMO_AUTH', 'true') !== 'true') {
            throw $exception;
        }
    }

    if (!$user && env_value('DEMO_AUTH', 'true') === 'true') {
        return [
            'id' => 1,
            'full_name' => 'Avery Stone',
            'username' => 'avery.ai',
            'email' => 'avery@example.com',
            'phone' => '+1 415 555 0198',
            'avatar' => null,
            'cover_photo' => null,
            'bio' => 'Building beautiful learning systems with AI, analytics, and modern product craft.',
            'address' => 'San Francisco, CA',
            'gender' => 'Non-binary',
            'date_of_birth' => '1998-06-21',
            'website' => 'https://example.com',
            'twitter' => 'https://x.com/example',
            'linkedin' => 'https://linkedin.com/in/example',
            'github' => 'https://github.com/example',
            'is_verified' => 1,
            'status' => 'active',
            'membership_plan' => 'Quantum Pro',
            'storage_used_mb' => 8240,
            'storage_limit_mb' => 20480,
            'rank_title' => 'Diamond Learner',
            'last_login_at' => date('Y-m-d H:i:s', strtotime('-12 minutes')),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'two_factor_enabled' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 years')),
        ];
    }

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    return $user;
}

function demo_dashboard_data(): array
{
    return [
            'login_history' => [
                ['device_name' => 'MacBook Pro • Chrome', 'ip_address' => '192.168.1.24', 'location' => 'San Francisco, US', 'logged_in_at' => date('Y-m-d H:i:s', strtotime('-12 minutes'))],
                ['device_name' => 'iPhone 15 • Safari', 'ip_address' => '172.16.0.8', 'location' => 'Oakland, US', 'logged_in_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
                ['device_name' => 'Windows Studio • Edge', 'ip_address' => '10.0.0.4', 'location' => 'Remote', 'logged_in_at' => date('Y-m-d H:i:s', strtotime('-4 days'))],
            ],
            'achievements' => [
                ['title' => 'AI Pathfinder', 'icon' => '✦', 'description' => 'Completed 25 advanced lessons'],
                ['title' => 'Quiz Master', 'icon' => '⚡', 'description' => 'Scored above 90% five times'],
                ['title' => 'Streak Hero', 'icon' => '🔥', 'description' => '30 day learning streak'],
            ],
            'notifications' => [
                ['title' => 'New certificate unlocked', 'body' => 'Your Data Analytics certificate is ready.', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
                ['title' => 'Security check passed', 'body' => 'A trusted device was confirmed.', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
            ],
            'favorites' => [['title' => 'Neural UI Kit'], ['title' => 'PDO Security Guide'], ['title' => 'Advanced Quiz Pack']],
            'downloads' => [['title' => 'Dashboard Template', 'downloaded_at' => date('Y-m-d')], ['title' => 'MySQL Cheat Sheet', 'downloaded_at' => date('Y-m-d', strtotime('-2 days'))]],
            'quiz_results' => [['quiz_title' => 'PHP Security', 'score' => 96, 'created_at' => date('Y-m-d')], ['quiz_title' => 'MySQL Indexes', 'score' => 88, 'created_at' => date('Y-m-d', strtotime('-3 days'))]],
            'settings' => ['theme' => 'dark', 'language' => 'en', 'email_notifications' => 1, 'push_notifications' => 1, 'profile_visibility' => 'members'],
        ];
}

function dashboard_data(int $userId): array
{
    if (env_value('DEMO_AUTH', 'true') === 'true') {
        try {
            db();
        } catch (Throwable) {
            return demo_dashboard_data();
        }
    }

    $tables = [
        'login_history' => 'SELECT device_name, ip_address, location, logged_in_at FROM login_history WHERE user_id = :id ORDER BY logged_in_at DESC LIMIT 5',
        'achievements' => 'SELECT title, icon, description FROM achievements WHERE user_id = :id ORDER BY earned_at DESC LIMIT 6',
        'notifications' => 'SELECT title, body, created_at FROM notifications WHERE user_id = :id ORDER BY created_at DESC LIMIT 5',
        'favorites' => 'SELECT title FROM favorites WHERE user_id = :id ORDER BY created_at DESC LIMIT 5',
        'downloads' => 'SELECT title, downloaded_at FROM downloads WHERE user_id = :id ORDER BY downloaded_at DESC LIMIT 5',
        'quiz_results' => 'SELECT quiz_title, score, created_at FROM quiz_results WHERE user_id = :id ORDER BY created_at DESC LIMIT 5',
    ];

    $data = [];
    foreach ($tables as $key => $sql) {
        $stmt = db()->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $data[$key] = $stmt->fetchAll();
    }

    $stmt = db()->prepare('SELECT theme, language, email_notifications, push_notifications, profile_visibility FROM user_settings WHERE user_id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $data['settings'] = $stmt->fetch() ?: ['theme' => 'dark', 'language' => 'en', 'email_notifications' => 1, 'push_notifications' => 1, 'profile_visibility' => 'members'];

    return $data;
}

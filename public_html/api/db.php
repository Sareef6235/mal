<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

const DB_HOST = 'localhost';
const DB_NAME = 'saas_dashboard';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function input(): array
{
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?: []) : [];
}

function json_response(bool $success, string $message = '', array $data = [], int $status = 200): void
{
    http_response_code($status);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

function require_auth(): array
{
    if (empty($_SESSION['user'])) {
        json_response(false, 'Unauthorized', [], 401);
    }
    return $_SESSION['user'];
}

function require_admin(): array
{
    $user = require_auth();
    if (($user['role'] ?? 'user') !== 'admin') {
        json_response(false, 'Forbidden', [], 403);
    }
    return $user;
}

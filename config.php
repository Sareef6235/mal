<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'mal';
const DB_USER = 'root';
const DB_PASS = '';

const SESSION_TIMEOUT_SECONDS = 1800;
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_MINUTES = 5;
const OTP_MAX_ATTEMPTS = 3;
const OTP_EXPIRY_MINUTES = 10;
const REMEMBER_COOKIE_NAME = 'remember_token';
const REMEMBER_COOKIE_DAYS = 30;
const APP_FROM_EMAIL = 'no-reply@example.com';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

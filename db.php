<?php

declare(strict_types=1);

/**
 * Shared DB connector using /config.php.
 */
function app_config(): array
{
    static $config;
    if ($config !== null) {
        return $config;
    }

    $config = require __DIR__ . '/config.php';
    $timezone = $config['app']['timezone'] ?? 'UTC';
    date_default_timezone_set($timezone);

    return $config;
}

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = app_config()['db'];
    $charset = $cfg['charset'] ?? 'utf8mb4';
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        (int)$cfg['port'],
        $cfg['database'],
        $charset
    );

    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

date_default_timezone_set($config['app']['timezone']);

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['db']['host'],
    $config['db']['port'],
    $config['db']['database'],
    $config['db']['charset']
);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $config['db']['username'], $config['db']['password'], $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

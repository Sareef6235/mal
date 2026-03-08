<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'hvernued_p2';
$user = getenv('DB_USER') ?: 'hvernued_cpses_hvnqmd5ph8';
$pass = getenv('DB_PASS') ?: 'Zirect@1618*1##';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Database connection failed. Please configure DB settings.');
}

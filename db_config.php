<?php
declare(strict_types=1);

$dbType = null;
if (isset($_POST['db_type'])) {
    $candidate = (string)$_POST['db_type'];
    if (in_array($candidate, ['sqlite', 'mysql'], true)) {
        $dbType = $candidate;
    }
}

return [
    'db_type' => $dbType ?: (getenv('DB_DRIVER') ?: 'sqlite'),
    'db' => [
        'driver' => $dbType ?: (getenv('DB_DRIVER') ?: 'sqlite'),
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'hvernued_p2',
        'user' => getenv('DB_USER') ?: 'hvernued_cpses_hvnqmd5ph8',
        'pass' => getenv('DB_PASS') ?: 'Zirect@1618*1##',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        'sqlite_path' => __DIR__ . '/data/madrasa.sqlite',
    ],
];

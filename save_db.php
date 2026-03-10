<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$type = (string)($_POST['db_type'] ?? $_GET['db_type'] ?? 'sqlite');
if (!in_array($type, ['sqlite', 'mysql'], true)) $type = 'sqlite';

$content = <<<'CFG'
<?php
declare(strict_types=1);

$dbType = '%s';

return [
    'db_type' => $dbType,
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
CFG;

file_put_contents(__DIR__ . '/db_config.php', sprintf($content, $type));

$from = (string)($_POST['from'] ?? 'select');
if ($from === 'mysql') { header('Location: database_mysql.php?saved=1'); exit; }
if ($from === 'sqlite') { header('Location: database_sqlite.php?saved=1'); exit; }
header('Location: database_select.php?saved=1');
exit;

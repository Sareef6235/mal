<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$type = (string)($_POST['db_type'] ?? $_GET['db_type'] ?? 'sqlite');
if (!in_array($type, ['sqlite','mysql'], true)) $type = 'sqlite';
file_put_contents(__DIR__ . '/db_config.php', "<?php
return ['db_type' => '" . $type . "'];
");
$from = (string)($_POST['from'] ?? 'select');
if ($from === 'mysql') { header('Location: database_mysql.php?saved=1'); exit; }
if ($from === 'sqlite') { header('Location: database_sqlite.php?saved=1'); exit; }
header('Location: database_select.php?saved=1');
exit;

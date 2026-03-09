<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$type = (string)($_POST['db_type'] ?? 'sqlite');
if (!in_array($type, ['sqlite','mysql'], true)) $type = 'sqlite';
file_put_contents(__DIR__ . '/db_config.php', "<?php\nreturn ['db_type' => '" . $type . "'];\n");
header('Location: database_select.php?saved=1');
exit;

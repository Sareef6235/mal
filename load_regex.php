<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
$rows = db()->query('SELECT * FROM regex_rules ORDER BY is_default DESC, priority ASC, name ASC')->fetchAll();
json_response(['success' => true, 'rules' => $rows]);

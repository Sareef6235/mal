<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_csrf();
$id = (int)($_POST['id'] ?? 0);
db()->prepare('DELETE FROM regex_rules WHERE id = ?')->execute([$id]);
json_response(['success' => true]);

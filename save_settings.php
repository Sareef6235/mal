<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_csrf();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$types = ['confidence_level'=>'integer','file_retention_days'=>'integer','maximum_upload_size'=>'integer','auto_cleanup'=>'boolean','remove_extra_spaces'=>'boolean','auto_detect_tables'=>'boolean','multi_regex_processing'=>'boolean','debug_mode'=>'boolean'];
foreach ($input as $key => $value) {
    if (!preg_match('/^[a-z0-9_]+$/', (string)$key)) continue;
    save_setting((string)$key, $value, $types[$key] ?? 'string');
}
json_response(['success' => true]);

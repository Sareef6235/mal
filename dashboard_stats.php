<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
$stats = [
    'imports' => (int)db()->query('SELECT COUNT(*) FROM ocr_imports')->fetchColumn(),
    'imported_rows' => (int)db()->query('SELECT COUNT(*) FROM monthly_plan')->fetchColumn(),
    'active_rules' => (int)db()->query('SELECT COUNT(*) FROM regex_rules WHERE is_active = 1')->fetchColumn(),
    'failed_imports' => (int)db()->query("SELECT COUNT(*) FROM ocr_imports WHERE status = 'failed'")->fetchColumn(),
];
json_response(['success' => true, 'stats' => $stats]);

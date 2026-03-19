<?php
require_once __DIR__ . '/../app/helpers/bootstrap.php';

use App\Services\FileConversionService;

$service = new FileConversionService();
$deleted = $service->cleanupExpired(__DIR__ . '/../app/storage/uploads', (int) ($GLOBALS['app_config']['app']['auto_delete_minutes'] ?? 30));

echo sprintf("Deleted %d expired upload(s).\n", $deleted);

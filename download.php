<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/UploadController.php';

$config = require __DIR__ . '/config/config.php';
$uploadController = new UploadController($pdo, $config);
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    exit('Invalid file ID.');
}

$file = $uploadController->find($id);
if (!$file) {
    http_response_code(404);
    exit('File not found.');
}

$fullPath = __DIR__ . '/' . $file['file_path'];
if (!is_file($fullPath)) {
    http_response_code(404);
    exit('File is missing from storage.');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file['file_name']) . '"');
header('Content-Length: ' . filesize($fullPath));
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
exit;

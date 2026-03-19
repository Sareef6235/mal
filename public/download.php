<?php
require_once __DIR__ . '/../app/helpers/bootstrap.php';

$file = basename($_GET['file'] ?? '');
$path = __DIR__ . '/../app/storage/exports/' . $file;

if ($file === '' || !is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);

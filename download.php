<?php
declare(strict_types=1);

$baseDir = realpath(__DIR__ . '/uploads');
if ($baseDir === false) { http_response_code(500); exit('Upload directory missing.'); }

$file = (string)($_GET['file'] ?? '');
if ($file === '' || str_contains($file, "\0")) { http_response_code(400); exit('Invalid file request.'); }

$clean = ltrim(str_replace('..', '', str_replace('\\', '/', $file)), '/');
$fullPath = realpath($baseDir . '/' . $clean);
if ($fullPath === false || !str_starts_with($fullPath, $baseDir . DIRECTORY_SEPARATOR) || !is_file($fullPath)) {
    http_response_code(404);
    exit('File not found.');
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeMap = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
];

if (!isset($mimeMap[$ext])) { http_response_code(415); exit('Unsupported file type.'); }

$filename = basename($fullPath);
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeMap[$ext]);
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Length: ' . (string)filesize($fullPath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, must-revalidate');
header('Pragma: public');
readfile($fullPath);
exit;

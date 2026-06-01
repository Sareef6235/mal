<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_csrf();

if (empty($_FILES['file'])) {
    json_response(['success' => false, 'message' => 'No file received.'], 422);
}
$file = $_FILES['file'];
$maxSize = (int)get_setting('maximum_upload_size', MAX_UPLOAD_SIZE_FALLBACK);
if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0 || $file['size'] > $maxSize) {
    json_response(['success' => false, 'message' => 'Upload failed or exceeds maximum size.'], 422);
}
$allowed = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!isset($allowed[$mime])) {
    json_response(['success' => false, 'message' => 'Only PDF, JPG, JPEG, and PNG files are allowed.'], 422);
}
$stored = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
$path = UPLOAD_DIR . '/' . $stored;
if (!move_uploaded_file($file['tmp_name'], $path)) {
    json_response(['success' => false, 'message' => 'Unable to store uploaded file.'], 500);
}
$stmt = db()->prepare('INSERT INTO ocr_imports (original_name, stored_name, file_path, mime_type, file_size) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$file['name'], $stored, 'uploads/' . $stored, $mime, $file['size']]);
$id = (int)db()->lastInsertId();
log_event($id, 'info', 'File uploaded', ['name' => $file['name'], 'mime' => $mime]);
json_response(['success' => true, 'import_id' => $id, 'file' => ['name' => e($file['name']), 'size' => (int)$file['size'], 'mime' => $mime, 'path' => 'uploads/' . $stored]]);

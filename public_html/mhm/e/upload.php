<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'POST requests only.'], 405);
}

if (empty($_FILES['files'])) {
    json_response(['success' => false, 'message' => 'No files were uploaded.'], 422);
}

$files = $_FILES['files'];
$results = [];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$total = is_array($files['name']) ? count($files['name']) : 0;

for ($i = 0; $i < $total; $i++) {
    $original = (string) $files['name'][$i];
    $tmp = (string) $files['tmp_name'][$i];
    $size = (int) $files['size'][$i];
    $error = (int) $files['error'][$i];

    if ($error !== UPLOAD_ERR_OK) {
        $results[] = ['success' => false, 'file' => $original, 'message' => 'Upload failed with error code ' . $error];
        continue;
    }
    if ($size <= 0 || $size > MAX_UPLOAD_BYTES) {
        $results[] = ['success' => false, 'file' => $original, 'message' => 'File exceeds the 15 MB limit or is empty.'];
        continue;
    }

    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $mime = (string) $finfo->file($tmp);
    if (!in_array($extension, ALLOWED_EXTENSIONS, true) || !in_array($mime, ALLOWED_MIME_TYPES, true)) {
        $results[] = ['success' => false, 'file' => $original, 'message' => 'Unsupported file type.'];
        continue;
    }

    $stored = bin2hex(random_bytes(16)) . '.' . $extension;
    $target = uploads_path($stored);
    if (!move_uploaded_file($tmp, $target)) {
        $results[] = ['success' => false, 'file' => $original, 'message' => 'Could not store uploaded file.'];
        continue;
    }
    chmod($target, 0644);

    $stmt = db()->prepare('INSERT INTO upload_history (original_name, stored_name, mime_type, file_size, status) VALUES (:original, :stored, :mime, :size, "uploaded")');
    $stmt->execute([':original' => $original, ':stored' => $stored, ':mime' => $mime, ':size' => $size]);
    $historyId = (int) db()->lastInsertId();

    $extracted = extract_text_from_file($target, $mime);
    $rawText = $extracted['text'];
    $regex = apply_regex_rules($rawText);
    $status = $extracted['success'] ? 'completed' : 'needs_ocr_tool';

    $update = db()->prepare('UPDATE upload_history SET raw_text = :raw, cleaned_text = :clean, status = :status, error_message = :error WHERE id = :id');
    $update->execute([
        ':raw' => $rawText,
        ':clean' => $regex['text'],
        ':status' => $status,
        ':error' => $extracted['success'] ? null : $extracted['message'],
        ':id' => $historyId,
    ]);

    $results[] = [
        'success' => $extracted['success'],
        'id' => $historyId,
        'file' => $original,
        'message' => $extracted['message'],
        'raw_text' => $rawText,
        'cleaned_text' => $regex['text'],
        'applied_rules' => $regex['applied'],
    ];
}

json_response(['success' => true, 'results' => $results, 'history' => recent_history(8)]);

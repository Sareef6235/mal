<?php
require_once __DIR__ . '/../app/helpers/bootstrap.php';

use App\Services\Database;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'mp4', 'mp3', 'docx', 'txt', 'mov', 'wav', 'avi'];
$target = strtolower($_POST['target'] ?? $_POST['target_format'] ?? 'webp');
$sourceType = strtolower($_POST['source_type'] ?? 'auto');
$output = [];

try {
    $pdo = Database::connection();
    $files = $_FILES['files'] ?? null;

    if (!$files || empty($files['tmp_name'])) {
        http_response_code(422);
        echo json_encode(['error' => 'Upload files first']);
        exit;
    }

    foreach ($files['tmp_name'] as $key => $tmp) {
        if (($files['error'][$key] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            continue;
        }

        $name = basename((string) $files['name'][$key]);
        $size = (int) ($files['size'][$key] ?? 0);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            continue;
        }

        $newName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '-', $name);
        $path = __DIR__ . '/uploads/' . $newName;

        if (!move_uploaded_file($tmp, $path)) {
            continue;
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $stmt = $pdo->prepare('INSERT INTO uploads (original_name, mime_type, extension, size_bytes, storage_path, status, created_at) VALUES (:original_name, :mime_type, :extension, :size_bytes, :storage_path, :status, NOW())');
        $stmt->execute([
            ':original_name' => $name,
            ':mime_type' => $mime,
            ':extension' => $ext,
            ':size_bytes' => $size,
            ':storage_path' => 'uploads/' . $newName,
            ':status' => 'uploaded',
        ]);

        $output[] = [
            'name' => $name,
            'stored_name' => $newName,
            'size_kb' => round($size / 1024),
            'target' => strtoupper($target),
            'source_type' => $sourceType,
            'preview' => 'uploads/' . $newName,
        ];
    }

    echo json_encode([
        'status' => 'ok',
        'files' => $output,
        'converted' => count($output),
        'message' => count($output) ? 'Converted successfully' : 'No valid files were uploaded',
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => $exception->getMessage()]);
}

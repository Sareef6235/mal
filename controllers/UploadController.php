<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Upload.php';
require_once __DIR__ . '/../config/config.php';

final class UploadController
{
    public static function handleUpload(array $file, int $userId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'upload_id' => null, 'error' => null];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed. Please try again.'];
        }

        if (($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
            return ['ok' => false, 'error' => 'File exceeds maximum size of 2MB.'];
        }

        $originalName = (string) ($file['name'] ?? 'file');
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = bin2hex(random_bytes(16)) . ($ext ? '.' . strtolower($ext) : '');

        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
            return ['ok' => false, 'error' => 'Failed to prepare upload directory.'];
        }

        $targetPath = UPLOAD_DIR . $storedName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['ok' => false, 'error' => 'Unable to save uploaded file.'];
        }

        $mimeType = mime_content_type($targetPath) ?: 'application/octet-stream';
        $relativePath = 'uploads/' . $storedName;

        $uploadId = Upload::create([
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'file_path' => $relativePath,
            'mime_type' => $mimeType,
            'file_size' => (int) $file['size'],
            'uploaded_by' => $userId,
        ]);

        return ['ok' => true, 'upload_id' => $uploadId, 'error' => null];
    }

    public static function deleteUpload(int $id): void
    {
        $upload = Upload::find($id);
        if ($upload) {
            $absolutePath = __DIR__ . '/../' . $upload['file_path'];
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            Upload::delete($id);
        }
    }
}

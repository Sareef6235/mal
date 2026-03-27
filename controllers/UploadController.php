<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Upload.php';

class UploadController
{
    private Upload $uploadModel;

    public function __construct(private PDO $pdo, private array $config)
    {
        $this->uploadModel = new Upload($this->pdo);
    }

    public function recent(int $limit = 10): array
    {
        return $this->uploadModel->recent($limit);
    }

    public function store(array $file): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'message' => 'Invalid upload request.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload failed with error code: ' . $file['error']];
        }

        if ((int) $file['size'] > (int) $this->config['upload']['max_size']) {
            return ['success' => false, 'message' => 'File exceeds 2MB limit.'];
        }

        $uploadDir = $this->config['upload']['directory'];
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $originalName = basename((string) $file['name']);
        $cleanFileName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName) ?: 'file';
        $extension = pathinfo($cleanFileName, PATHINFO_EXTENSION);
        $randomName = bin2hex(random_bytes(16));
        $storedName = $extension !== '' ? $randomName . '.' . strtolower($extension) : $randomName;
        $destination = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storedName;

        if (!is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Potential file upload attack detected.'];
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'message' => 'Could not move uploaded file.'];
        }

        @chmod($destination, 0644);

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($destination) ?: 'application/octet-stream';
        $relativePath = 'uploads/' . $storedName;

        $saved = $this->uploadModel->create(
            $cleanFileName,
            $storedName,
            $relativePath,
            (int) $file['size'],
            $mimeType
        );

        if (!$saved) {
            @unlink($destination);
            return ['success' => false, 'message' => 'Failed to save upload metadata.'];
        }

        return ['success' => true, 'message' => 'File uploaded successfully.'];
    }

    public function destroy(int $id): array
    {
        $upload = $this->uploadModel->find($id);
        if (!$upload) {
            return ['success' => false, 'message' => 'File not found.'];
        }

        $deleted = $this->uploadModel->delete($id);
        if (!$deleted) {
            return ['success' => false, 'message' => 'Could not delete file record.'];
        }

        $fullPath = __DIR__ . '/../' . $upload['file_path'];
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }

        return ['success' => true, 'message' => 'File deleted successfully.'];
    }

    public function find(int $id): ?array
    {
        return $this->uploadModel->find($id);
    }
}

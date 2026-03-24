<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/UploadController.php';

final class NotificationController
{
    public static function create(array $post, array $files, int $userId): array
    {
        $title = trim((string) ($post['title'] ?? ''));
        $message = trim((string) ($post['message'] ?? ''));

        if ($title === '' || $message === '') {
            return ['ok' => false, 'error' => 'Title and message are required.'];
        }

        $uploadResult = UploadController::handleUpload($files['attachment'] ?? [], $userId);
        if (!$uploadResult['ok']) {
            return $uploadResult;
        }

        Notification::create([
            'title' => $title,
            'message' => $message,
            'upload_id' => $uploadResult['upload_id'],
            'created_by' => $userId,
        ]);

        return ['ok' => true, 'error' => null];
    }

    public static function update(int $id, array $post, array $files, int $userId): array
    {
        $title = trim((string) ($post['title'] ?? ''));
        $message = trim((string) ($post['message'] ?? ''));

        if ($title === '' || $message === '') {
            return ['ok' => false, 'error' => 'Title and message are required.'];
        }

        $existing = Notification::findWithUpload($id);
        if (!$existing) {
            return ['ok' => false, 'error' => 'Notification not found.'];
        }

        $uploadId = $existing['upload_id'] ? (int) $existing['upload_id'] : null;
        $uploadResult = UploadController::handleUpload($files['attachment'] ?? [], $userId);

        if (!$uploadResult['ok']) {
            return $uploadResult;
        }

        if ($uploadResult['upload_id']) {
            $uploadId = (int) $uploadResult['upload_id'];
        }

        Notification::update($id, [
            'title' => $title,
            'message' => $message,
            'upload_id' => $uploadId,
        ]);

        return ['ok' => true, 'error' => null];
    }
}

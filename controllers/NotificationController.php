<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Notification.php';

class NotificationController
{
    private Notification $notificationModel;

    public function __construct(private PDO $pdo)
    {
        $this->notificationModel = new Notification($this->pdo);
    }

    public function index(): array
    {
        return $this->notificationModel->all();
    }

    public function count(): int
    {
        return $this->notificationModel->count();
    }

    public function store(array $input): array
    {
        $title = trim($input['title'] ?? '');
        $message = sanitize_rich_text($input['message'] ?? '');

        if ($title === '' || $message === '') {
            return ['success' => false, 'message' => 'Title and message are required.'];
        }

        if (mb_strlen($title) > 255) {
            return ['success' => false, 'message' => 'Title cannot exceed 255 characters.'];
        }

        $created = $this->notificationModel->create($title, $message);

        return [
            'success' => $created,
            'message' => $created ? 'Notification created successfully.' : 'Failed to create notification.',
        ];
    }

    public function update(int $id, array $input): array
    {
        $title = trim($input['title'] ?? '');
        $message = sanitize_rich_text($input['message'] ?? '');

        if ($title === '' || $message === '') {
            return ['success' => false, 'message' => 'Title and message are required.'];
        }

        $updated = $this->notificationModel->update($id, $title, $message);

        return [
            'success' => $updated,
            'message' => $updated ? 'Notification updated successfully.' : 'Failed to update notification.',
        ];
    }

    public function destroy(int $id): array
    {
        $deleted = $this->notificationModel->delete($id);

        return [
            'success' => $deleted,
            'message' => $deleted ? 'Notification deleted successfully.' : 'Failed to delete notification.',
        ];
    }

    public function find(int $id): ?array
    {
        return $this->notificationModel->find($id);
    }
}

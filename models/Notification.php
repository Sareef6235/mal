<?php

declare(strict_types=1);

class Notification
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, title, message, created_at, updated_at FROM notifications ORDER BY created_at DESC, id DESC');
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS total FROM notifications');
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, message, created_at, updated_at FROM notifications WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $notification = $stmt->fetch();

        return $notification ?: null;
    }

    public function create(string $title, string $message): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications (title, message) VALUES (:title, :message)');
        return $stmt->execute([
            'title' => $title,
            'message' => $message,
        ]);
    }

    public function update(int $id, string $title, string $message): bool
    {
        $stmt = $this->pdo->prepare('UPDATE notifications SET title = :title, message = :message WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'title' => $title,
            'message' => $message,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM notifications WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

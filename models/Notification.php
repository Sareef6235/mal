<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Notification
{
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO notifications (title, message, upload_id, created_by, created_at, updated_at)
                VALUES (:title, :message, :upload_id, :created_by, NOW(), NOW())';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $sql = 'UPDATE notifications
                SET title = :title, message = :message, upload_id = :upload_id, updated_at = NOW()
                WHERE id = :id';
        $stmt = Database::connection()->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'message' => $data['message'],
            'upload_id' => $data['upload_id'],
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM notifications WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function count(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
    }

    public static function allWithUpload(?string $search = null): array
    {
        $sql = 'SELECT n.*, u.original_name, u.file_path
                FROM notifications n
                LEFT JOIN uploads u ON u.id = n.upload_id';

        $params = [];
        if ($search !== null && $search !== '') {
            $sql .= ' WHERE n.title LIKE :term OR n.message LIKE :term';
            $params['term'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY n.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function findWithUpload(int $id): ?array
    {
        $sql = 'SELECT n.*, u.original_name, u.file_path
                FROM notifications n
                LEFT JOIN uploads u ON u.id = n.upload_id
                WHERE n.id = :id
                LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}

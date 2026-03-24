<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class Upload
{
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO uploads (original_name, stored_name, file_path, mime_type, file_size, uploaded_by, created_at, updated_at)
                VALUES (:original_name, :stored_name, :file_path, :mime_type, :file_size, :uploaded_by, NOW(), NOW())';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($data);

        return (int) Database::connection()->lastInsertId();
    }

    public static function all(): array
    {
        $sql = 'SELECT u.*, usr.username
                FROM uploads u
                LEFT JOIN users usr ON usr.id = u.uploaded_by
                ORDER BY u.created_at DESC';
        return Database::connection()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $sql = 'SELECT * FROM uploads WHERE id = :id LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM uploads WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

<?php

declare(strict_types=1);

class Upload
{
    public function __construct(private PDO $pdo)
    {
    }

    public function recent(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare('SELECT id, file_name, stored_name, file_path, file_size, file_type, upload_date FROM uploads ORDER BY upload_date DESC, id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function create(string $fileName, string $storedName, string $filePath, int $fileSize, string $fileType): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO uploads (file_name, stored_name, file_path, file_size, file_type) 
             VALUES (:file_name, :stored_name, :file_path, :file_size, :file_type)'
        );

        return $stmt->execute([
            'file_name' => $fileName,
            'stored_name' => $storedName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_type' => $fileType,
        ]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, file_name, stored_name, file_path, file_size, file_type, upload_date FROM uploads WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $upload = $stmt->fetch();

        return $upload ?: null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM uploads WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

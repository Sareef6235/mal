<?php
require_once __DIR__ . '/config.php';

function fetch_subjects(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM subjects ORDER BY name')->fetchAll();
}

function fetch_classes(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
}

function file_category(string $mime, string $extension): string
{
    $extension = strtolower($extension);
    if ($mime === 'application/pdf' || $extension === 'pdf') {
        return 'pdf';
    }
    if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        return 'image';
    }
    if (str_starts_with($mime, 'video/') || in_array($extension, ['mp4', 'webm', 'mov'], true)) {
        return 'video';
    }
    if (in_array($extension, ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt'], true)) {
        return 'doc';
    }
    if (in_array($extension, ['zip', 'rar', '7z'], true)) {
        return 'zip';
    }
    return 'other';
}

function category_folder(string $category): string
{
    return match ($category) {
        'pdf' => 'pdf',
        'doc' => 'docs',
        'image' => 'images',
        'video' => 'videos',
        'zip' => 'zip',
        default => 'docs',
    };
}

function ensure_upload_dirs(): void
{
    foreach (['pdf', 'docs', 'images', 'videos', 'zip', 'thumbnails'] as $dir) {
        $path = UPLOAD_ROOT . '/' . $dir;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}

function allowed_mimes(): array
{
    return [
        'application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm', 'video/quicktime', 'application/zip',
        'application/x-zip-compressed', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'application/x-7z-compressed', 'application/vnd.rar'
    ];
}

function store_uploaded_file(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please retry.');
    }
    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('File exceeds the 100 MB upload limit.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($mime, allowed_mimes(), true) && !in_array($extension, ['rar', '7z'], true)) {
        throw new RuntimeException('Unsupported file type.');
    }

    ensure_upload_dirs();
    $hash = hash_file('sha256', $file['tmp_name']);
    $category = file_category($mime, $extension);
    $folder = category_folder($category);
    $safeName = $hash . ($extension ? '.' . $extension : '');
    $target = UPLOAD_ROOT . '/' . $folder . '/' . $safeName;

    if (!file_exists($target) && !move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Could not store uploaded file.');
    }

    return [
        'hash' => $hash,
        'category' => $category,
        'mime' => $mime,
        'size' => (int)$file['size'],
        'file_name' => $safeName,
        'original' => basename($file['name']),
        'path' => PUBLIC_UPLOAD_ROOT . '/' . $folder . '/' . $safeName,
    ];
}

function store_thumbnail(?array $file): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Thumbnail upload failed.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    if (!str_starts_with($mime, 'image/')) {
        throw new RuntimeException('Thumbnail must be an image.');
    }
    ensure_upload_dirs();
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
    $name = bin2hex(random_bytes(18)) . '.' . $extension;
    $target = UPLOAD_ROOT . '/thumbnails/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Could not store thumbnail.');
    }
    return PUBLIC_UPLOAD_ROOT . '/thumbnails/' . $name;
}

function material_query(PDO $pdo, array $params = []): array
{
    $where = ['m.is_published = 1'];
    $bind = [];
    if (!empty($params['subject'])) {
        $where[] = 's.slug = :subject';
        $bind['subject'] = $params['subject'];
    }
    if (!empty($params['class'])) {
        $where[] = 'c.slug = :class';
        $bind['class'] = $params['class'];
    }
    if (!empty($params['q'])) {
        $where[] = '(m.title LIKE :q OR m.description LIKE :q OR m.tags LIKE :q)';
        $bind['q'] = '%' . $params['q'] . '%';
    }
    $order = match ($params['sort'] ?? 'newest') {
        'popular' => 'm.downloads_count DESC, m.views_count DESC',
        'trending' => '(m.views_count + (m.downloads_count * 3)) DESC, m.created_at DESC',
        default => 'm.created_at DESC',
    };
    $page = max(1, (int)($params['page'] ?? 1));
    $limit = min(24, max(6, (int)($params['limit'] ?? 12)));
    $offset = ($page - 1) * $limit;

    $sql = "SELECT m.*, s.name subject_name, s.slug subject_slug, s.color subject_color, c.name class_name, c.slug class_slug
            FROM materials m
            JOIN subjects s ON s.id = m.subject_id
            JOIN classes c ON c.id = m.class_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$order}
            LIMIT {$limit} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bind);
    return $stmt->fetchAll();
}

function icon_for_type(string $type): string
{
    return match ($type) {
        'pdf' => 'bi-file-earmark-pdf-fill',
        'image' => 'bi-file-earmark-image-fill',
        'video' => 'bi-play-btn-fill',
        'zip' => 'bi-file-earmark-zip-fill',
        'doc' => 'bi-file-earmark-text-fill',
        default => 'bi-file-earmark-fill',
    };
}

function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
}

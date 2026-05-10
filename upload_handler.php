<?php
require_once __DIR__ . '/functions.php';
require_admin();
verify_csrf();

try {
    $title = trim($_POST['title'] ?? '');
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $classId = (int)($_POST['class_id'] ?? 0);
    if ($title === '' || $subjectId <= 0 || $classId <= 0) {
        throw new RuntimeException('Title, subject, and class are required.');
    }
    if (empty($_FILES['materials']['name'][0])) {
        throw new RuntimeException('Choose at least one file.');
    }

    $thumbnail = store_thumbnail($_FILES['thumbnail'] ?? null);
    $created = 0;
    $duplicates = 0;
    foreach ($_FILES['materials']['name'] as $i => $name) {
        $file = [
            'name' => $_FILES['materials']['name'][$i],
            'type' => $_FILES['materials']['type'][$i],
            'tmp_name' => $_FILES['materials']['tmp_name'][$i],
            'error' => $_FILES['materials']['error'][$i],
            'size' => $_FILES['materials']['size'][$i],
        ];
        $stored = store_uploaded_file($file);
        $exists = $pdo->prepare('SELECT id FROM materials WHERE file_name = ? LIMIT 1');
        $exists->execute([$stored['file_name']]);
        if ($exists->fetchColumn()) {
            $duplicates++;
            continue;
        }
        $materialTitle = count($_FILES['materials']['name']) > 1 ? $title . ' - ' . pathinfo($name, PATHINFO_FILENAME) : $title;
        $baseSlug = slugify($materialTitle);
        $slug = $baseSlug . '-' . bin2hex(random_bytes(3));
        $stmt = $pdo->prepare('INSERT INTO materials (title, slug, description, subject_id, class_id, tags, file_name, original_file_name, file_path, file_type, mime_type, file_size, thumbnail_path, is_published, is_featured, is_premium, created_by) VALUES (:title, :slug, :description, :subject_id, :class_id, :tags, :file_name, :original_file_name, :file_path, :file_type, :mime_type, :file_size, :thumbnail_path, :is_published, :is_featured, :is_premium, :created_by)');
        $stmt->execute([
            'title' => $materialTitle,
            'slug' => $slug,
            'description' => trim($_POST['description'] ?? ''),
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'tags' => trim($_POST['tags'] ?? ''),
            'file_name' => $stored['file_name'],
            'original_file_name' => $stored['original'],
            'file_path' => $stored['path'],
            'file_type' => $stored['category'],
            'mime_type' => $stored['mime'],
            'file_size' => $stored['size'],
            'thumbnail_path' => $thumbnail,
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_premium' => isset($_POST['is_premium']) ? 1 : 0,
            'created_by' => $_SESSION['admin_id'],
        ]);
        $created++;
    }
    json_response(['ok' => true, 'message' => "{$created} material(s) uploaded. {$duplicates} duplicate(s) skipped."]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => $e->getMessage()], 422);
}

<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

function isAdmin(PDO $pdo): bool {
    $userId = (int)($_SERVER['HTTP_X_USER_ID'] ?? $_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    if ($userId <= 0) return false;
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();
    return $row && $row['role'] === 'admin';
}

try {
    $pdo = db();
    $action = $_GET['action'] ?? '';

    if ($action === 'list') {
        $rows = $pdo->query('SELECT id, name, file FROM sounds ORDER BY id DESC')->fetchAll();
        echo json_encode(['ok' => true, 'sounds' => $rows]);
        exit;
    }

    if (!isAdmin($pdo)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Admin only']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new RuntimeException('Invalid sound id');

        $stmt = $pdo->prepare('SELECT file FROM sounds WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) throw new RuntimeException('Sound not found');

        $path = __DIR__ . '/' . ltrim($row['file'], '/');
        if (is_file($path)) @unlink($path);

        $del = $pdo->prepare('DELETE FROM sounds WHERE id = :id');
        $del->execute([':id' => $id]);
        echo json_encode(['ok' => true, 'message' => 'Sound deleted']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['sound'])) throw new RuntimeException('No file uploaded');

    $file = $_FILES['sound'];
    if ((int)$file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload error');
    if ((int)$file['size'] > 2 * 1024 * 1024) throw new RuntimeException('File exceeds 2MB limit');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    $allowed = ['audio/mpeg', 'audio/mp3'];
    if (!in_array($mime, $allowed, true)) throw new RuntimeException('Only MP3 files are allowed');

    $name = trim((string)($_POST['name'] ?? 'Custom Sound'));
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $filename = $safeName . '_' . time() . '.mp3';

    $dir = __DIR__ . '/uploads/sounds';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Cannot create sound directory');

    $target = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('Failed to move uploaded file');

    $publicFile = 'uploads/sounds/' . $filename;
    $ins = $pdo->prepare('INSERT INTO sounds (name, file) VALUES (:name, :file)');
    $ins->execute([':name' => $name, ':file' => $publicFile]);

    echo json_encode(['ok' => true, 'message' => 'Sound uploaded', 'file' => $publicFile]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}

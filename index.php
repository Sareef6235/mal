<?php
session_start();

const MAX_FILE_SIZE = 250 * 1024 * 1024; // 250 MB per file
const BASE_UPLOAD_DIR = __DIR__ . '/uploads';
const BASE_UPLOAD_URL = 'uploads';
const DB_PATH = __DIR__ . '/data/study_materials.sqlite';

$subjects = ['Qur\'an Studies', 'Hadith', 'Fiqh', 'Aqidah', 'Arabic Language', 'English', 'Mathematics', 'Science', 'History', 'Computer Studies'];
$classes = ['Nursery', 'KG', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Alim Year 1', 'Alim Year 2'];

$fileBuckets = [
    'pdf' => ['folder' => 'pdf', 'icon' => 'bi-file-earmark-pdf', 'color' => '#ff5c8a', 'extensions' => ['pdf'], 'mimes' => ['application/pdf']],
    'doc' => ['folder' => 'docs', 'icon' => 'bi-file-earmark-word', 'color' => '#52a7ff', 'extensions' => ['doc', 'docx'], 'mimes' => ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']],
    'video' => ['folder' => 'videos', 'icon' => 'bi-play-btn', 'color' => '#a855f7', 'extensions' => ['mp4'], 'mimes' => ['video/mp4']],
    'image' => ['folder' => 'images', 'icon' => 'bi-file-earmark-image', 'color' => '#22d3ee', 'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], 'mimes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']],
    'zip' => ['folder' => 'zip', 'icon' => 'bi-file-earmark-zip', 'color' => '#fbbf24', 'extensions' => ['zip'], 'mimes' => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip']],
    'other' => ['folder' => 'other', 'icon' => 'bi-file-earmark-richtext', 'color' => '#8bffca', 'extensions' => ['ppt', 'pptx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'odt', 'odp', 'ods'], 'mimes' => []],
];

function ensureStorage(): void
{
    $folders = ['pdf', 'docs', 'videos', 'images', 'zip', 'other', 'thumbnails'];
    foreach ($folders as $folder) {
        $path = BASE_UPLOAD_DIR . '/' . $folder;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
    if (!is_dir(dirname(DB_PATH))) {
        mkdir(dirname(DB_PATH), 0755, true);
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    ensureStorage();
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS materials (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        subject TEXT NOT NULL,
        class_name TEXT NOT NULL,
        description TEXT,
        tags TEXT,
        bucket TEXT NOT NULL,
        original_name TEXT NOT NULL,
        stored_name TEXT NOT NULL,
        file_path TEXT NOT NULL,
        file_url TEXT NOT NULL,
        thumbnail_url TEXT,
        mime_type TEXT NOT NULL,
        extension TEXT NOT NULL,
        file_size INTEGER NOT NULL,
        checksum TEXT NOT NULL UNIQUE,
        is_published INTEGER NOT NULL DEFAULT 0,
        is_featured INTEGER NOT NULL DEFAULT 0,
        is_premium INTEGER NOT NULL DEFAULT 0,
        views INTEGER NOT NULL DEFAULT 0,
        downloads INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL
    )");
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonResponse(['ok' => false, 'message' => 'Security token expired. Refresh the page and try again.'], 419);
    }
}

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

function bucketForFile(string $extension, string $mime): array
{
    global $fileBuckets;
    foreach ($fileBuckets as $bucket => $meta) {
        if (in_array($extension, $meta['extensions'], true)) {
            if ($bucket === 'other' || empty($meta['mimes']) || in_array($mime, $meta['mimes'], true)) {
                return [$bucket, $meta];
            }
        }
    }
    return ['other', $fileBuckets['other']];
}

function safeSlug(string $name): string
{
    $name = strtolower(pathinfo($name, PATHINFO_FILENAME));
    $name = preg_replace('/[^a-z0-9]+/i', '-', $name) ?: 'material';
    return trim($name, '-') ?: 'material';
}

function safeDownloadName(string $name): string
{
    return preg_replace('/[^A-Za-z0-9._-]/', '_', basename($name)) ?: 'material';
}

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $size = max($bytes, 0);
    $unit = 0;
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    return number_format($size, $unit === 0 ? 0 : 1) . ' ' . $units[$unit];
}

function uploadThumbnail(?array $thumbnail): ?string
{
    if (!$thumbnail || ($thumbnail['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($thumbnail['error'] !== UPLOAD_ERR_OK || $thumbnail['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Thumbnail must be a valid image under 5 MB.');
    }
    $mime = mime_content_type($thumbnail['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Thumbnail must be JPG, PNG, WEBP, or GIF.');
    }
    $stored = 'thumb-' . bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
    $target = BASE_UPLOAD_DIR . '/thumbnails/' . $stored;
    if (!move_uploaded_file($thumbnail['tmp_name'], $target)) {
        throw new RuntimeException('Could not save thumbnail.');
    }
    return BASE_UPLOAD_URL . '/thumbnails/' . $stored;
}

function handleUpload(): void
{
    verifyCsrf();
    $title = trim($_POST['title'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $className = trim($_POST['class_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tags = trim($_POST['tags'] ?? '');

    if ($title === '' || $subject === '' || $className === '') {
        jsonResponse(['ok' => false, 'message' => 'Title, subject, and class are required.'], 422);
    }
    if (empty($_FILES['materials']['name'][0])) {
        jsonResponse(['ok' => false, 'message' => 'Please choose at least one study material.'], 422);
    }

    try {
        $thumbnailUrl = uploadThumbnail($_FILES['thumbnail'] ?? null);
        $created = [];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $pdo = db();

        foreach ($_FILES['materials']['name'] as $index => $originalName) {
            $tmp = $_FILES['materials']['tmp_name'][$index];
            $error = $_FILES['materials']['error'][$index];
            $size = (int) $_FILES['materials']['size'][$index];

            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException("Upload failed for {$originalName}.");
            }
            if ($size <= 0 || $size > MAX_FILE_SIZE) {
                throw new RuntimeException("{$originalName} exceeds the 250 MB limit.");
            }
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($extension === '') {
                throw new RuntimeException("{$originalName} needs a file extension.");
            }
            $blockedExtensions = ['php', 'phtml', 'phar', 'cgi', 'pl', 'asp', 'aspx', 'jsp', 'sh', 'exe', 'bat', 'cmd', 'com', 'msi', 'dll'];
            if (in_array($extension, $blockedExtensions, true)) {
                throw new RuntimeException("{$originalName} is not an allowed study material format.");
            }
            $mime = $finfo->file($tmp) ?: 'application/octet-stream';
            [$bucket, $meta] = bucketForFile($extension, $mime);
            $checksum = hash_file('sha256', $tmp);
            $exists = $pdo->prepare('SELECT id, title FROM materials WHERE checksum = ? LIMIT 1');
            $exists->execute([$checksum]);
            if ($exists->fetch()) {
                throw new RuntimeException("Duplicate prevented: {$originalName} has already been uploaded.");
            }

            $storedName = safeSlug($originalName) . '-' . substr($checksum, 0, 12) . '.' . $extension;
            $targetDir = BASE_UPLOAD_DIR . '/' . $meta['folder'];
            $targetPath = $targetDir . '/' . $storedName;
            if (!move_uploaded_file($tmp, $targetPath)) {
                throw new RuntimeException("Could not save {$originalName}.");
            }

            $fileUrl = BASE_UPLOAD_URL . '/' . $meta['folder'] . '/' . $storedName;
            $materialThumb = $thumbnailUrl ?: ($bucket === 'image' ? $fileUrl : null);
            $stmt = $pdo->prepare('INSERT INTO materials
                (title, subject, class_name, description, tags, bucket, original_name, stored_name, file_path, file_url, thumbnail_url, mime_type, extension, file_size, checksum, is_published, is_featured, is_premium, created_at)
                VALUES (:title, :subject, :class_name, :description, :tags, :bucket, :original_name, :stored_name, :file_path, :file_url, :thumbnail_url, :mime_type, :extension, :file_size, :checksum, :is_published, :is_featured, :is_premium, :created_at)');
            $stmt->execute([
                ':title' => $title,
                ':subject' => $subject,
                ':class_name' => $className,
                ':description' => $description,
                ':tags' => $tags,
                ':bucket' => $bucket,
                ':original_name' => $originalName,
                ':stored_name' => $storedName,
                ':file_path' => $targetPath,
                ':file_url' => $fileUrl,
                ':thumbnail_url' => $materialThumb,
                ':mime_type' => $mime,
                ':extension' => $extension,
                ':file_size' => $size,
                ':checksum' => $checksum,
                ':is_published' => isset($_POST['is_published']) ? 1 : 0,
                ':is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                ':is_premium' => isset($_POST['is_premium']) ? 1 : 0,
                ':created_at' => gmdate('c'),
            ]);
            $created[] = ['id' => (int) $pdo->lastInsertId(), 'name' => $originalName, 'url' => $fileUrl, 'bucket' => $bucket, 'size' => formatBytes($size)];
        }
        jsonResponse(['ok' => true, 'message' => count($created) . ' material(s) uploaded successfully.', 'files' => $created]);
    } catch (Throwable $exception) {
        jsonResponse(['ok' => false, 'message' => $exception->getMessage()], 422);
    }
}

function handleDelete(): void
{
    verifyCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    $pdo = db();
    $stmt = $pdo->prepare('SELECT file_path FROM materials WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        jsonResponse(['ok' => false, 'message' => 'Material not found.'], 404);
    }
    if (is_file($row['file_path'])) {
        unlink($row['file_path']);
    }
    $pdo->prepare('DELETE FROM materials WHERE id = ?')->execute([$id]);
    jsonResponse(['ok' => true, 'message' => 'Material deleted.']);
}

function handlePreview(): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM materials WHERE id = ?');
    $stmt->execute([$id]);
    $file = $stmt->fetch();
    if (!$file || !is_file($file['file_path'])) {
        http_response_code(404);
        exit('File not found.');
    }
    $pdo->prepare('UPDATE materials SET views = views + 1 WHERE id = ?')->execute([$id]);
    header('Content-Type: ' . $file['mime_type']);
    header('Content-Disposition: inline; filename="' . safeDownloadName($file['original_name']) . '"');
    header('Content-Length: ' . filesize($file['file_path']));
    readfile($file['file_path']);
    exit;
}

function handleDownload(): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM materials WHERE id = ?');
    $stmt->execute([$id]);
    $file = $stmt->fetch();
    if (!$file || !is_file($file['file_path'])) {
        http_response_code(404);
        exit('File not found.');
    }
    $pdo->prepare('UPDATE materials SET downloads = downloads + 1 WHERE id = ?')->execute([$id]);
    header('Content-Type: ' . $file['mime_type']);
    header('Content-Disposition: attachment; filename="' . safeDownloadName($file['original_name']) . '"');
    header('Content-Length: ' . filesize($file['file_path']));
    readfile($file['file_path']);
    exit;
}

function iconForBucket(string $bucket): array
{
    global $fileBuckets;
    $meta = $fileBuckets[$bucket] ?? $fileBuckets['other'];
    return [$meta['icon'], $meta['color']];
}

ensureStorage();
$action = $_GET['action'] ?? '';
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleUpload();
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleDelete();
}
if ($action === 'preview') {
    handlePreview();
}
if ($action === 'download') {
    handleDownload();
}

$pdo = db();
$materials = $pdo->query('SELECT * FROM materials ORDER BY datetime(created_at) DESC LIMIT 24')->fetchAll();
$totalFiles = (int) $pdo->query('SELECT COUNT(*) FROM materials')->fetchColumn();
$totalDownloads = (int) $pdo->query('SELECT COALESCE(SUM(downloads), 0) FROM materials')->fetchColumn();
$totalViews = (int) $pdo->query('SELECT COALESCE(SUM(views), 0) FROM materials')->fetchColumn();
$totalSize = (int) $pdo->query('SELECT COALESCE(SUM(file_size), 0) FROM materials')->fetchColumn();
$csrf = csrfToken();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title>Study Materials Upload Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-1: #030712;
            --bg-2: #09112d;
            --glass: rgba(255, 255, 255, .085);
            --glass-strong: rgba(255, 255, 255, .14);
            --border: rgba(255, 255, 255, .18);
            --text: #f8fbff;
            --muted: #aeb9d4;
            --cyan: #22d3ee;
            --blue: #3b82f6;
            --purple: #a855f7;
            --pink: #ec4899;
            --success: #34d399;
            --warning: #fbbf24;
            --shadow: 0 24px 70px rgba(0, 0, 0, .42);
            --radius-xl: 28px;
            --sidebar-width: 292px;
        }

        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 12% 10%, rgba(34, 211, 238, .32), transparent 28rem),
                radial-gradient(circle at 86% 18%, rgba(168, 85, 247, .32), transparent 30rem),
                radial-gradient(circle at 50% 88%, rgba(59, 130, 246, .26), transparent 34rem),
                linear-gradient(135deg, var(--bg-1), var(--bg-2) 52%, #160526);
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: linear-gradient(to bottom, rgba(0,0,0,.9), transparent 80%);
        }

        .aurora {
            position: fixed;
            inset: -30%;
            z-index: -1;
            background: conic-gradient(from 120deg, rgba(34,211,238,.24), rgba(168,85,247,.2), rgba(236,72,153,.18), rgba(59,130,246,.2), rgba(34,211,238,.24));
            filter: blur(90px);
            animation: spinAurora 22s linear infinite;
            opacity: .7;
        }
        @keyframes spinAurora { to { transform: rotate(360deg) scale(1.06); } }

        .glass {
            background: linear-gradient(145deg, rgba(255,255,255,.14), rgba(255,255,255,.055));
            border: 1px solid var(--border);
            box-shadow: var(--shadow), inset 0 1px 0 rgba(255,255,255,.16);
            backdrop-filter: blur(24px);
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            min-height: 82px;
            border-bottom: 1px solid rgba(255,255,255,.12);
            background: rgba(4, 9, 25, .66);
            backdrop-filter: blur(24px);
        }
        .brand-orb, .avatar, .fab {
            background: linear-gradient(135deg, var(--cyan), var(--blue), var(--purple));
            box-shadow: 0 0 34px rgba(34, 211, 238, .36);
        }
        .brand-orb { width: 48px; height: 48px; border-radius: 16px; display: grid; place-items: center; }
        .search-pill {
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(255,255,255,.08);
            border-radius: 999px;
            color: var(--text);
            min-width: min(420px, 42vw);
        }
        .search-pill input { background: transparent; border: 0; color: var(--text); outline: 0; width: 100%; }
        .search-pill input::placeholder { color: rgba(248,251,255,.54); }

        .layout { display: grid; grid-template-columns: var(--sidebar-width) minmax(0, 1fr); gap: 24px; padding: 24px; }
        .sidebar {
            position: sticky;
            top: 106px;
            height: calc(100vh - 130px);
            border-radius: var(--radius-xl);
            padding: 18px;
            transition: transform .35s ease, opacity .35s ease;
        }
        .nav-link-premium {
            display: flex; align-items: center; gap: 12px;
            padding: 13px 14px; margin-bottom: 8px;
            color: var(--muted); text-decoration: none; border-radius: 18px;
            transition: all .28s ease; position: relative; overflow: hidden;
        }
        .nav-link-premium:hover, .nav-link-premium.active {
            color: white; transform: translateX(6px);
            background: linear-gradient(90deg, rgba(34,211,238,.18), rgba(168,85,247,.13));
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.12), 0 12px 28px rgba(0,0,0,.22);
        }
        .nav-link-premium i { font-size: 1.1rem; color: var(--cyan); }

        .hero-card { border-radius: 34px; padding: clamp(24px, 4vw, 44px); position: relative; overflow: hidden; }
        .hero-card::after {
            content: ''; position: absolute; width: 360px; height: 360px; right: -120px; top: -140px;
            background: radial-gradient(circle, rgba(34,211,238,.42), transparent 68%);
            animation: float 7s ease-in-out infinite;
        }
        @keyframes float { 50% { transform: translateY(18px) translateX(-12px); } }
        .eyebrow { color: var(--cyan); letter-spacing: .16em; font-size: .76rem; font-weight: 800; text-transform: uppercase; }
        .headline { font-size: clamp(2rem, 5vw, 4.7rem); line-height: .95; font-weight: 900; letter-spacing: -.07em; }
        .gradient-text { background: linear-gradient(90deg, #fff, var(--cyan), #d8b4fe); -webkit-background-clip: text; color: transparent; }

        .stat-card, .upload-card, .material-card, .control-card { border-radius: var(--radius-xl); transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease; }
        .hover-lift:hover { transform: translateY(-8px); border-color: rgba(34,211,238,.45); box-shadow: 0 28px 80px rgba(34,211,238,.14), var(--shadow); }
        .stat-card { padding: 20px; min-height: 132px; }
        .stat-icon { width: 46px; height: 46px; border-radius: 16px; display:grid; place-items:center; background: rgba(255,255,255,.1); color: var(--cyan); }
        .counter { font-size: 2rem; font-weight: 900; }

        .upload-zone {
            min-height: 250px;
            border: 1.7px dashed rgba(34,211,238,.5);
            border-radius: 28px;
            background: linear-gradient(145deg, rgba(34,211,238,.11), rgba(168,85,247,.09));
            display: grid; place-items: center; text-align: center; padding: 28px;
            transition: all .28s ease; cursor: pointer; position: relative; overflow: hidden;
        }
        .upload-zone::before, .shimmer::before {
            content: ''; position: absolute; inset: 0; transform: translateX(-120%);
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.16), transparent);
            animation: shimmer 3.2s infinite;
        }
        @keyframes shimmer { 55%, 100% { transform: translateX(120%); } }
        .upload-zone.dragover { transform: scale(1.015); border-color: white; box-shadow: 0 0 42px rgba(34,211,238,.28); }
        .upload-orb { width: 84px; height: 84px; border-radius: 28px; display:grid; place-items:center; margin: 0 auto 16px; background: linear-gradient(135deg, rgba(34,211,238,.28), rgba(168,85,247,.28)); box-shadow: inset 8px 8px 18px rgba(255,255,255,.08), inset -10px -10px 24px rgba(0,0,0,.22); }

        .form-control, .form-select {
            color: var(--text) !important;
            background-color: rgba(255,255,255,.08) !important;
            border: 1px solid rgba(255,255,255,.14) !important;
            border-radius: 16px !important;
            min-height: 48px;
        }
        .form-control::placeholder { color: rgba(248,251,255,.45); }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 .25rem rgba(34,211,238,.16) !important; border-color: rgba(34,211,238,.65) !important; }
        .form-select option { color: #111827; }
        .form-switch .form-check-input { width: 3.1em; height: 1.55em; background-color: rgba(255,255,255,.12); border-color: rgba(255,255,255,.2); }
        .form-check-input:checked { background-color: var(--cyan); border-color: var(--cyan); }

        .btn-neon {
            border: 0; color: white; font-weight: 800; border-radius: 18px; padding: 13px 20px;
            background: linear-gradient(135deg, var(--cyan), var(--blue), var(--purple));
            box-shadow: 0 14px 35px rgba(34,211,238,.24), inset 0 1px 0 rgba(255,255,255,.25);
            position: relative; overflow: hidden; transition: transform .25s ease, box-shadow .25s ease;
        }
        .btn-neon:hover { transform: translateY(-3px); box-shadow: 0 18px 50px rgba(168,85,247,.34); }
        .ripple { position: absolute; border-radius: 50%; transform: scale(0); animation: ripple .7s linear; background: rgba(255,255,255,.45); pointer-events:none; }
        @keyframes ripple { to { transform: scale(4); opacity: 0; } }

        .file-chip { border-radius: 16px; padding: 12px; background: rgba(255,255,255,.075); border: 1px solid rgba(255,255,255,.1); animation: fadeUp .45s ease both; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
        .progress { height: 12px; background: rgba(255,255,255,.1); border-radius: 99px; overflow: hidden; }
        .progress-bar { background: linear-gradient(90deg, var(--cyan), var(--purple)); box-shadow: 0 0 18px rgba(34,211,238,.4); }

        .material-card { overflow: hidden; padding: 16px; }
        .preview-thumb { height: 156px; border-radius: 22px; background: linear-gradient(145deg, rgba(255,255,255,.12), rgba(255,255,255,.04)); display:grid; place-items:center; overflow:hidden; position:relative; }
        .preview-thumb img, .preview-thumb video { width: 100%; height: 100%; object-fit: cover; }
        .file-badge { border-radius: 999px; padding: 6px 10px; background: rgba(255,255,255,.1); color: white; font-size: .78rem; }
        .action-btn { width: 38px; height: 38px; display:grid; place-items:center; border-radius: 13px; border: 1px solid rgba(255,255,255,.12); color: white; background: rgba(255,255,255,.08); transition: all .25s ease; text-decoration:none; }
        .action-btn:hover { transform: translateY(-3px); background: rgba(34,211,238,.16); color: white; }

        .fab { position: fixed; right: 24px; bottom: 24px; z-index: 1031; width: 62px; height: 62px; border-radius: 22px; display:grid; place-items:center; color:white; border:0; font-size:1.45rem; }
        .offcanvas { background: rgba(5,10,25,.88); backdrop-filter: blur(26px); color: white; }
        .modal-content, .swal2-popup { background: rgba(9, 17, 45, .82) !important; color: white !important; border: 1px solid rgba(255,255,255,.16) !important; border-radius: 28px !important; backdrop-filter: blur(26px); }
        .accordion-item { background: rgba(255,255,255,.06); color: white; border: 1px solid rgba(255,255,255,.1); border-radius: 18px !important; overflow: hidden; margin-bottom: 10px; }
        .accordion-button { background: rgba(255,255,255,.04); color: white; }
        .accordion-button:not(.collapsed) { background: rgba(34,211,238,.12); color: white; box-shadow: none; }
        .table { --bs-table-color: var(--text); --bs-table-bg: transparent; --bs-table-border-color: rgba(255,255,255,.1); }
        .pagination .page-link { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.12); color: white; }

        [data-reveal] { opacity: 0; transform: translateY(24px); transition: opacity .7s ease, transform .7s ease; }
        [data-reveal].revealed { opacity: 1; transform: none; }

        @media (max-width: 991.98px) {
            .layout { grid-template-columns: 1fr; padding: 16px; }
            .sidebar { display: none; }
            .topbar { min-height: 72px; }
            .search-pill { min-width: 0; width: 100%; order: 3; }
            .headline { letter-spacing: -.045em; }
        }
        @media (max-width: 575.98px) {
            .hero-card { border-radius: 26px; }
            .upload-zone { min-height: 210px; }
            .preview-thumb { height: 132px; }
        }
    </style>
</head>
<body>
<div class="aurora"></div>
<nav class="topbar navbar navbar-expand-lg px-3 px-lg-4">
    <div class="container-fluid gap-3">
        <button class="btn btn-neon d-lg-none p-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="Open menu"><i class="bi bi-list fs-4"></i></button>
        <a class="navbar-brand d-flex align-items-center gap-3 text-white fw-black m-0" href="#">
            <span class="brand-orb"><i class="bi bi-stars fs-4"></i></span>
            <span class="d-none d-sm-block"><span class="d-block fw-bold">Madrasa Materials</span><small class="text-white-50">Premium Upload Cloud</small></span>
        </a>
        <div class="search-pill d-flex align-items-center gap-2 px-3 py-2 ms-lg-auto">
            <i class="bi bi-search text-info"></i>
            <input id="globalSearch" type="search" placeholder="Search materials, classes, subjects...">
        </div>
        <button class="action-btn position-relative" aria-label="Notifications"><i class="bi bi-bell"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span></button>
        <div class="dropdown">
            <button class="btn d-flex align-items-center gap-2 text-white border-0" data-bs-toggle="dropdown">
                <span class="avatar d-inline-grid place-items-center rounded-circle" style="width:42px;height:42px;place-items:center;"><i class="bi bi-person-fill"></i></span>
                <span class="d-none d-md-block text-start"><strong>Admin</strong><small class="d-block text-white-50">Super user</small></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<?php ob_start(); ?>
<a class="nav-link-premium active" href="#upload"><i class="bi bi-cloud-arrow-up"></i><span>Upload Materials</span></a>
<a class="nav-link-premium" href="#dashboard"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>
<a class="nav-link-premium" href="#manage"><i class="bi bi-folder2-open"></i><span>Manage Files</span></a>
<a class="nav-link-premium" href="#subjects"><i class="bi bi-book"></i><span>Subjects</span></a>
<a class="nav-link-premium" href="#classes"><i class="bi bi-mortarboard"></i><span>Classes</span></a>
<a class="nav-link-premium" href="#stats"><i class="bi bi-bar-chart-line"></i><span>Statistics</span></a>
<a class="nav-link-premium" href="#"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a>
<?php $navMarkup = ob_get_clean(); ?>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header"><h5 class="offcanvas-title">Navigation</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div>
    <div class="offcanvas-body"><?= $navMarkup ?></div>
</div>

<main class="layout">
    <aside class="sidebar glass" data-reveal>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div><div class="eyebrow">Control</div><h5 class="mb-0">Admin Studio</h5></div><i class="bi bi-command text-info fs-4"></i>
        </div>
        <?= $navMarkup ?>
        <div class="glass p-3 rounded-4 mt-4 shimmer position-relative overflow-hidden">
            <small class="text-white-50">Storage used</small>
            <div class="d-flex justify-content-between mt-2"><strong><?= e(formatBytes($totalSize)) ?></strong><span class="text-info">Live</span></div>
            <div class="progress mt-3"><div class="progress-bar" style="width:42%"></div></div>
        </div>
    </aside>

    <section class="content">
        <section class="hero-card glass hover-lift mb-4" id="dashboard" data-reveal>
            <div class="row align-items-center g-4 position-relative z-1">
                <div class="col-lg-8">
                    <div class="eyebrow mb-3">AI-style futuristic dashboard</div>
                    <h1 class="headline mb-3">Upload, preview, and manage <span class="gradient-text">study materials</span> beautifully.</h1>
                    <p class="lead text-white-50 mb-4">Securely publish PDFs, DOC/DOCX, images, MP4 videos, ZIP archives, and classroom resources into automatically routed folders with instant previews and downloads.</p>
                    <div class="d-flex flex-wrap gap-3"><a href="#upload" class="btn btn-neon">Start Uploading <i class="bi bi-arrow-up-right ms-2"></i></a><a href="#manage" class="btn btn-outline-light rounded-4 px-4">Manage Library</a></div>
                </div>
                <div class="col-lg-4">
                    <div class="glass rounded-5 p-4">
                        <div class="d-flex align-items-center gap-3 mb-3"><i class="bi bi-shield-lock fs-2 text-info"></i><div><strong>Secure pipeline</strong><small class="d-block text-white-50">CSRF, PDO, validation</small></div></div>
                        <div class="accordion" id="premiumAccordion">
                            <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#accOne">Auto file routing</button></h2><div id="accOne" class="accordion-collapse collapse show" data-bs-parent="#premiumAccordion"><div class="accordion-body text-white-50">PDFs, docs, videos, images, ZIP files, and other materials land in dedicated folders.</div></div></div>
                            <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accTwo">Premium UX states</button></h2><div id="accTwo" class="accordion-collapse collapse" data-bs-parent="#premiumAccordion"><div class="accordion-body text-white-50">Drag/drop, progress, toasts, modal previews, shimmer loading, and success animation.</div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-4" id="stats">
            <?php foreach ([['Files', $totalFiles, 'bi-files'], ['Downloads', $totalDownloads, 'bi-cloud-download'], ['Views', $totalViews, 'bi-eye'], ['Storage', formatBytes($totalSize), 'bi-hdd-stack']] as $stat): ?>
            <div class="col-6 col-xl-3" data-reveal><div class="stat-card glass hover-lift"><div class="stat-icon mb-3"><i class="bi <?= e($stat[2]) ?>"></i></div><div class="counter" data-counter="<?= e((string) $stat[1]) ?>"><?= e((string) $stat[1]) ?></div><div class="text-white-50"><?= e($stat[0]) ?></div></div></div>
            <?php endforeach; ?>
        </section>

        <section class="row g-4" id="upload">
            <div class="col-xl-7" data-reveal>
                <form id="uploadForm" class="upload-card glass p-3 p-md-4 hover-lift" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-4"><div><div class="eyebrow">Upload console</div><h2 class="mb-0 fw-black">New Study Material</h2></div><span class="file-badge"><i class="bi bi-lightning-charge text-warning"></i> Multi-file ready</span></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Material Title</label><input class="form-control" name="title" required maxlength="180" placeholder="e.g. Fiqh Chapter 04 Notes"></div>
                        <div class="col-md-3"><label class="form-label">Subject</label><select class="form-select" name="subject" required><option value="">Choose</option><?php foreach ($subjects as $subject): ?><option><?= e($subject) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Class</label><select class="form-select" name="class_name" required><option value="">Choose</option><?php foreach ($classes as $class): ?><option><?= e($class) ?></option><?php endforeach; ?></select></div>
                        <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" placeholder="Short learning outcome, lesson notes, instructions..."></textarea></div>
                        <div class="col-md-8"><label class="form-label">Tags</label><input class="form-control" name="tags" placeholder="hadith, exam, worksheet, tajweed"></div>
                        <div class="col-md-4"><label class="form-label">Thumbnail</label><input class="form-control" type="file" name="thumbnail" accept="image/*"></div>
                    </div>
                    <div class="row g-3 my-3">
                        <div class="col-sm-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" id="published" checked><label class="form-check-label" for="published">Publish now</label></div></div>
                        <div class="col-sm-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_featured" id="featured"><label class="form-check-label" for="featured">Featured</label></div></div>
                        <div class="col-sm-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_premium" id="premium"><label class="form-check-label" for="premium">Premium</label></div></div>
                    </div>
                    <label class="upload-zone" id="dropZone">
                        <input class="d-none" id="materialsInput" type="file" name="materials[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.zip,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.rtf,.odt,.odp,.ods">
                        <span><span class="upload-orb"><i class="bi bi-cloud-arrow-up fs-1"></i></span><strong class="fs-4 d-block">Drop files here or click to browse</strong><span class="text-white-50">PDF, DOC/DOCX, images, MP4, ZIP, and study materials up to 250 MB each</span><span id="autoType" class="d-block mt-3 file-badge">File type auto-detection ready</span></span>
                    </label>
                    <div id="filePreview" class="row g-2 mt-3"></div>
                    <div class="mt-4"><div class="d-flex justify-content-between small mb-2"><span id="uploadState">Waiting for files</span><span id="uploadPercent">0%</span></div><div class="progress"><div id="uploadProgress" class="progress-bar" style="width:0%"></div></div></div>
                    <div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-neon flex-grow-1" type="submit"><i class="bi bi-rocket-takeoff me-2"></i>Upload Materials</button><button class="btn btn-outline-light rounded-4 px-4" type="reset">Reset</button></div>
                </form>
            </div>

            <div class="col-xl-5" data-reveal>
                <div class="control-card glass p-3 p-md-4 hover-lift h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3"><div><div class="eyebrow">Live filters</div><h3 class="mb-0">Manage Files</h3></div><i class="bi bi-sliders2 text-info fs-3"></i></div>
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6"><input id="librarySearch" class="form-control" placeholder="Live search..."></div>
                        <div class="col-sm-6"><select id="sortSelect" class="form-select"><option value="newest">Sort by newest</option><option value="downloads">Sort by downloads</option><option value="views">Sort by views</option></select></div>
                    </div>
                    <div class="table-responsive rounded-4">
                        <table class="table align-middle mb-0" id="materialsTable">
                            <thead><tr><th>File</th><th>Type</th><th>Views</th><th>Downloads</th></tr></thead>
                            <tbody>
                            <?php foreach (array_slice($materials, 0, 7) as $material): ?>
                                <tr data-search="<?= e(strtolower($material['title'] . ' ' . $material['subject'] . ' ' . $material['class_name'])) ?>" data-downloads="<?= (int) $material['downloads'] ?>" data-views="<?= (int) $material['views'] ?>" data-newest="<?= e($material['created_at']) ?>">
                                    <td><strong><?= e($material['title']) ?></strong><small class="d-block text-white-50"><?= e($material['original_name']) ?></small></td><td><span class="file-badge"><?= e(strtoupper($material['extension'])) ?></span></td><td><?= (int) $material['views'] ?></td><td><?= (int) $material['downloads'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav class="mt-3"><ul class="pagination pagination-sm justify-content-end mb-0"><li class="page-item disabled"><a class="page-link">Prev</a></li><li class="page-item active"><a class="page-link">1</a></li><li class="page-item"><a class="page-link">2</a></li><li class="page-item"><a class="page-link">Next</a></li></ul></nav>
                </div>
            </div>
        </section>

        <section class="mt-4" id="manage" data-reveal>
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3"><div><div class="eyebrow">Material library</div><h2 class="fw-black mb-0">Preview & Download Center</h2></div><div class="d-flex gap-2"><button class="btn btn-outline-light rounded-4 filter-btn" data-filter="all">All</button><button class="btn btn-outline-info rounded-4 filter-btn" data-filter="pdf">PDF</button><button class="btn btn-outline-info rounded-4 filter-btn" data-filter="image">Images</button><button class="btn btn-outline-info rounded-4 filter-btn" data-filter="video">Videos</button></div></div>
            <div class="row g-3" id="materialGrid">
                <?php if (!$materials): ?>
                    <div class="col-12"><div class="glass text-center p-5 rounded-5 shimmer position-relative overflow-hidden"><i class="bi bi-inboxes display-3 text-info"></i><h3 class="mt-3">No materials yet</h3><p class="text-white-50 mb-0">Upload your first premium study resource to populate this management center.</p></div></div>
                <?php endif; ?>
                <?php foreach ($materials as $material): [$icon, $color] = iconForBucket($material['bucket']); ?>
                    <div class="col-sm-6 col-xxl-4 material-item" data-bucket="<?= e($material['bucket']) ?>" data-search="<?= e(strtolower($material['title'] . ' ' . $material['subject'] . ' ' . $material['class_name'] . ' ' . $material['tags'])) ?>" data-downloads="<?= (int) $material['downloads'] ?>" data-views="<?= (int) $material['views'] ?>" data-newest="<?= e($material['created_at']) ?>">
                        <article class="material-card glass hover-lift h-100">
                            <div class="preview-thumb mb-3">
                                <?php if ($material['thumbnail_url']): ?><img src="<?= e($material['thumbnail_url']) ?>" alt="<?= e($material['title']) ?> thumbnail"><?php elseif ($material['bucket'] === 'video'): ?><video src="<?= e($material['file_url']) ?>" muted preload="metadata"></video><?php else: ?><i class="bi <?= e($icon) ?> display-1" style="color:<?= e($color) ?>"></i><?php endif; ?>
                                <span class="position-absolute top-0 end-0 m-2 file-badge"><?= e(strtoupper($material['extension'])) ?></span>
                            </div>
                            <div class="d-flex justify-content-between gap-2"><div><h5 class="mb-1 text-truncate"><?= e($material['title']) ?></h5><p class="text-white-50 small mb-2"><?= e($material['subject']) ?> • <?= e($material['class_name']) ?></p></div><button class="action-btn favorite-btn" type="button"><i class="bi bi-heart"></i></button></div>
                            <div class="d-flex flex-wrap gap-2 mb-3"><span class="file-badge"><i class="bi bi-calendar3"></i> <?= e(date('M d, Y', strtotime($material['created_at']))) ?></span><span class="file-badge"><i class="bi bi-hdd"></i> <?= e(formatBytes((int) $material['file_size'])) ?></span></div>
                            <div class="d-flex justify-content-between text-white-50 small mb-3"><span><i class="bi bi-eye"></i> <?= (int) $material['views'] ?> views</span><span><i class="bi bi-download"></i> <?= (int) $material['downloads'] ?> downloads</span></div>
                            <div class="d-flex gap-2">
                                <button class="action-btn preview-btn" type="button" data-url="?action=preview&id=<?= (int) $material['id'] ?>" data-type="<?= e($material['bucket']) ?>" data-title="<?= e($material['title']) ?>"><i class="bi bi-eye"></i></button>
                                <a class="action-btn" href="?action=download&id=<?= (int) $material['id'] ?>"><i class="bi bi-download"></i></a>
                                <button class="action-btn share-btn" type="button" data-url="<?= e($material['file_url']) ?>"><i class="bi bi-share"></i></button>
                                <button class="action-btn" type="button"><i class="bi bi-pencil"></i></button>
                                <button class="action-btn delete-btn" type="button" data-id="<?= (int) $material['id'] ?>"><i class="bi bi-trash"></i></button>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </section>
</main>

<button class="fab" onclick="document.getElementById('upload').scrollIntoView({behavior:'smooth'})" aria-label="Upload"><i class="bi bi-plus-lg"></i></button>

<div class="modal fade" id="previewModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content"><div class="modal-header border-0"><h5 class="modal-title" id="previewTitle">Preview</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body" id="previewBody"></div></div></div></div>
<div class="toast-container position-fixed top-0 end-0 p-3"><div id="appToast" class="toast text-bg-dark border border-info" role="alert"><div class="toast-header bg-transparent text-white border-info"><i class="bi bi-stars text-info me-2"></i><strong class="me-auto">Madrasa Cloud</strong><button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button></div><div class="toast-body"></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const form = document.getElementById('uploadForm');
const input = document.getElementById('materialsInput');
const dropZone = document.getElementById('dropZone');
const preview = document.getElementById('filePreview');
const progress = document.getElementById('uploadProgress');
const percent = document.getElementById('uploadPercent');
const state = document.getElementById('uploadState');
const toastEl = document.getElementById('appToast');
const toast = new bootstrap.Toast(toastEl);

function showToast(message) { toastEl.querySelector('.toast-body').textContent = message; toast.show(); }
function fileIcon(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (ext === 'pdf') return ['bi-file-earmark-pdf', 'PDF'];
    if (['doc','docx'].includes(ext)) return ['bi-file-earmark-word', 'DOC'];
    if (['jpg','jpeg','png','gif','webp','svg'].includes(ext)) return ['bi-file-earmark-image', 'IMG'];
    if (ext === 'mp4') return ['bi-play-btn', 'MP4'];
    if (ext === 'zip') return ['bi-file-earmark-zip', 'ZIP'];
    return ['bi-file-earmark-richtext', 'FILE'];
}
function renderFiles(files) {
    preview.innerHTML = '';
    [...files].forEach((file, index) => {
        const [icon, label] = fileIcon(file);
        const mb = (file.size / 1024 / 1024).toFixed(2);
        preview.insertAdjacentHTML('beforeend', `<div class="col-md-6"><div class="file-chip d-flex align-items-center gap-3" style="animation-delay:${index * 60}ms"><i class="bi ${icon} fs-3 text-info"></i><div class="min-w-0 flex-grow-1"><strong class="d-block text-truncate">${file.name}</strong><small class="text-white-50">${label} • ${mb} MB</small></div></div></div>`);
    });
    document.getElementById('autoType').textContent = files.length ? `${files.length} file(s) detected and ready` : 'File type auto-detection ready';
}
['dragenter','dragover'].forEach(evt => dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.add('dragover'); }));
['dragleave','drop'].forEach(evt => dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.remove('dragover'); }));
dropZone.addEventListener('drop', e => { input.files = e.dataTransfer.files; renderFiles(input.files); });
input.addEventListener('change', () => renderFiles(input.files));
form.addEventListener('reset', () => setTimeout(() => { preview.innerHTML = ''; progress.style.width = '0%'; percent.textContent = '0%'; state.textContent = 'Waiting for files'; }, 0));
form.addEventListener('submit', e => {
    e.preventDefault();
    const data = new FormData(form);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '?action=upload');
    xhr.setRequestHeader('X-CSRF-Token', csrf);
    xhr.upload.addEventListener('progress', event => {
        if (!event.lengthComputable) return;
        const value = Math.round((event.loaded / event.total) * 100);
        progress.style.width = `${value}%`; percent.textContent = `${value}%`; state.textContent = value < 100 ? 'Uploading securely...' : 'Processing materials...';
    });
    xhr.onload = () => {
        let response = { ok: false, message: 'Unexpected server response.' };
        try { response = JSON.parse(xhr.responseText); } catch (error) {}
        if (xhr.status >= 200 && xhr.status < 300 && response.ok) {
            state.textContent = 'Upload complete';
            Swal.fire({ icon: 'success', title: 'Upload successful', text: response.message, confirmButtonColor: '#22d3ee' }).then(() => location.reload());
            showToast(response.message);
        } else {
            progress.style.width = '0%'; percent.textContent = '0%'; state.textContent = 'Upload failed';
            Swal.fire({ icon: 'error', title: 'Upload blocked', text: response.message, confirmButtonColor: '#ec4899' });
        }
    };
    xhr.onerror = () => Swal.fire({ icon: 'error', title: 'Network error', text: 'Could not complete upload.' });
    xhr.send(data);
});

document.querySelectorAll('.btn-neon').forEach(button => button.addEventListener('click', event => {
    const circle = document.createElement('span');
    const diameter = Math.max(button.clientWidth, button.clientHeight);
    circle.style.width = circle.style.height = `${diameter}px`;
    circle.style.left = `${event.clientX - button.getBoundingClientRect().left - diameter / 2}px`;
    circle.style.top = `${event.clientY - button.getBoundingClientRect().top - diameter / 2}px`;
    circle.classList.add('ripple'); button.appendChild(circle); setTimeout(() => circle.remove(), 700);
}));

const revealObserver = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('revealed'); }), { threshold: .12 });
document.querySelectorAll('[data-reveal]').forEach(el => revealObserver.observe(el));

document.querySelectorAll('.preview-btn').forEach(btn => btn.addEventListener('click', () => {
    const url = btn.dataset.url, type = btn.dataset.type, title = btn.dataset.title;
    const body = document.getElementById('previewBody'); document.getElementById('previewTitle').textContent = title;
    if (type === 'image') body.innerHTML = `<img class="img-fluid rounded-4 w-100" src="${url}" alt="${title}">`;
    else if (type === 'video') body.innerHTML = `<video class="w-100 rounded-4" src="${url}" controls autoplay></video>`;
    else if (type === 'pdf') body.innerHTML = `<iframe class="w-100 rounded-4" src="${url}" style="height:72vh;border:0"></iframe>`;
    else body.innerHTML = `<div class="text-center p-5"><i class="bi bi-file-earmark-arrow-down display-1 text-info"></i><h4 class="mt-3">Preview unavailable for this type</h4><p class="text-white-50">Download the file to open it in its native application.</p><a class="btn btn-neon" href="${url}" download>Download file</a></div>`;
    new bootstrap.Modal('#previewModal').show();
}));

document.querySelectorAll('.share-btn').forEach(btn => btn.addEventListener('click', async () => {
    const url = new URL(btn.dataset.url, location.href).href;
    if (navigator.share) await navigator.share({ title: 'Study Material', url }); else await navigator.clipboard.writeText(url);
    showToast('Share link copied.');
}));
document.querySelectorAll('.favorite-btn').forEach(btn => btn.addEventListener('click', () => btn.querySelector('i').classList.toggle('bi-heart-fill')));
document.querySelectorAll('.delete-btn').forEach(btn => btn.addEventListener('click', async () => {
    const result = await Swal.fire({ icon: 'warning', title: 'Delete material?', text: 'This removes the database record and uploaded file.', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#ec4899' });
    if (!result.isConfirmed) return;
    const data = new FormData(); data.append('id', btn.dataset.id); data.append('csrf_token', csrf);
    const res = await fetch('?action=delete', { method: 'POST', body: data }); const json = await res.json();
    if (json.ok) { showToast(json.message); btn.closest('.material-item').remove(); } else Swal.fire('Error', json.message, 'error');
}));

function applyLibraryFilters() {
    const q = (document.getElementById('librarySearch').value || document.getElementById('globalSearch').value || '').toLowerCase();
    document.querySelectorAll('.material-item, #materialsTable tbody tr').forEach(el => { el.style.display = el.dataset.search.includes(q) ? '' : 'none'; });
}
document.getElementById('librarySearch').addEventListener('input', applyLibraryFilters);
document.getElementById('globalSearch').addEventListener('input', applyLibraryFilters);
document.querySelectorAll('.filter-btn').forEach(btn => btn.addEventListener('click', () => {
    const filter = btn.dataset.filter;
    document.querySelectorAll('.material-item').forEach(card => { card.style.display = filter === 'all' || card.dataset.bucket === filter ? '' : 'none'; });
}));
document.getElementById('sortSelect').addEventListener('change', event => {
    const key = event.target.value;
    const grid = document.getElementById('materialGrid');
    [...grid.querySelectorAll('.material-item')].sort((a, b) => key === 'newest' ? b.dataset.newest.localeCompare(a.dataset.newest) : Number(b.dataset[key]) - Number(a.dataset[key])).forEach(card => grid.appendChild(card));
});
</script>
</body>
</html>

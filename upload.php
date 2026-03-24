<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/UploadController.php';

$config = require __DIR__ . '/config/config.php';
$uploadController = new UploadController($pdo, $config);
$notificationController = new NotificationController($pdo);
$notificationCount = $notificationController->count();
$activePage = 'upload';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $uploadController->store($_FILES['uploaded_file'] ?? []);
    set_flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect($result['success'] ? app_url('dashboard.php') : app_url('upload.php'));
}

$recentUploads = $uploadController->recent(12);

require __DIR__ . '/views/partials/header.php';
require __DIR__ . '/views/partials/flash.php';
?>

<section class="panel">
    <h2>Upload File</h2>
    <p class="hint">Allowed: any file type, max size 2MB.</p>
    <form method="POST" enctype="multipart/form-data" class="stack" id="uploadForm">
        <label for="uploaded_file">Select file</label>
        <input id="uploaded_file" type="file" name="uploaded_file" required>

        <button type="submit" class="btn">Upload</button>
    </form>
</section>

<section class="panel">
    <h2>Recent Uploads</h2>
    <?php if (empty($recentUploads)): ?>
        <p class="empty">No uploads yet.</p>
    <?php else: ?>
        <div class="upload-list">
            <?php foreach ($recentUploads as $upload): ?>
                <div class="upload-item">
                    <div>
                        <strong><?= e($upload['file_name']); ?></strong>
                        <p><?= e($upload['file_type']); ?> • <?= e(format_bytes((int) $upload['file_size'])); ?></p>
                    </div>
                    <a class="btn btn-light" href="<?= e(app_url('download.php?id=' . (int) $upload['id'])); ?>">Download</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/views/partials/footer.php'; ?>

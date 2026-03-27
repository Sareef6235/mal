<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/UploadController.php';

$config = require __DIR__ . '/config/config.php';
$notificationController = new NotificationController($pdo);
$uploadController = new UploadController($pdo, $config);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_notification') {
        $result = $notificationController->destroy((int) ($_POST['id'] ?? 0));
        set_flash($result['success'] ? 'success' : 'error', $result['message']);
        redirect(app_url('dashboard.php'));
    }

    if ($action === 'delete_upload') {
        $result = $uploadController->destroy((int) ($_POST['id'] ?? 0));
        set_flash($result['success'] ? 'success' : 'error', $result['message']);
        redirect(app_url('dashboard.php'));
    }
}

$notifications = $notificationController->index();
$recentUploads = $uploadController->recent(8);
$notificationCount = $notificationController->count();
$activePage = 'dashboard';

require __DIR__ . '/views/partials/header.php';
require __DIR__ . '/views/partials/flash.php';
?>

<section class="grid-two">
    <div>
        <div class="section-header">
            <h2>Latest Notifications</h2>
            <a class="btn" href="<?= e(app_url('notification.php')); ?>">+ Add New</a>
        </div>
        <?php if (empty($notifications)): ?>
            <p class="empty">No notifications available yet.</p>
        <?php else: ?>
            <div class="cards">
                <?php foreach ($notifications as $item): ?>
                    <article class="card">
                        <div class="card-head">
                            <h3><?= e($item['title']); ?></h3>
                            <span class="date"><?= e(date('Y-m-d H:i', strtotime($item['created_at']))); ?></span>
                        </div>
                        <div class="message"><?= $item['message']; ?></div>
                        <div class="card-actions">
                            <a class="btn btn-light" href="<?= e(app_url('notification.php?edit=' . (int) $item['id'])); ?>">Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this notification?');">
                                <input type="hidden" name="action" value="delete_notification">
                                <input type="hidden" name="id" value="<?= (int) $item['id']; ?>">
                                <button class="btn btn-danger" type="submit">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <aside>
        <div class="section-header">
            <h2>Recent Uploads</h2>
            <a class="btn" href="<?= e(app_url('upload.php')); ?>">Upload</a>
        </div>
        <?php if (empty($recentUploads)): ?>
            <p class="empty">No files uploaded yet.</p>
        <?php else: ?>
            <div class="upload-list">
                <?php foreach ($recentUploads as $upload): ?>
                    <div class="upload-item">
                        <div>
                            <strong><?= e($upload['file_name']); ?></strong>
                            <p><?= e($upload['file_type']); ?> • <?= e(format_bytes((int) $upload['file_size'])); ?></p>
                            <small><?= e(date('Y-m-d H:i', strtotime($upload['upload_date']))); ?></small>
                        </div>
                        <div class="card-actions">
                            <a class="btn btn-light" href="<?= e(app_url('download.php?id=' . (int) $upload['id'])); ?>">Download</a>
                            <form method="POST" onsubmit="return confirm('Delete this file?');">
                                <input type="hidden" name="action" value="delete_upload">
                                <input type="hidden" name="id" value="<?= (int) $upload['id']; ?>">
                                <button class="btn btn-danger" type="submit">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</section>

<?php require __DIR__ . '/views/partials/footer.php'; ?>

<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/UploadController.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/models/Upload.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid request token.');
        redirect('dashboard.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_notification') {
        $result = NotificationController::create($_POST, $_FILES, (int) $_SESSION['user_id']);
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Notification added.' : $result['error']);
    } elseif ($action === 'update_notification') {
        $id = (int) ($_POST['notification_id'] ?? 0);
        $result = NotificationController::update($id, $_POST, $_FILES, (int) $_SESSION['user_id']);
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Notification updated.' : $result['error']);
    } elseif ($action === 'delete_notification') {
        Notification::delete((int) ($_POST['notification_id'] ?? 0));
        flash('success', 'Notification deleted.');
    } elseif ($action === 'upload_file') {
        $result = UploadController::handleUpload($_FILES['attachment'] ?? [], (int) $_SESSION['user_id']);
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'File uploaded successfully.' : $result['error']);
    } elseif ($action === 'delete_upload') {
        UploadController::deleteUpload((int) ($_POST['upload_id'] ?? 0));
        flash('success', 'Upload deleted.');
    }

    redirect('dashboard.php');
}

$search = trim((string) ($_GET['search'] ?? ''));
$notifications = Notification::allWithUpload($search);
$uploads = Upload::all();
$totalNotifications = Notification::count();
$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0 ? Notification::findWithUpload($editId) : null;

$pageTitle = 'Dashboard';
require __DIR__ . '/views/partials/header.php';
?>

<header class="topbar">
    <h1>Admin Dashboard</h1>
    <div>
        <span class="stat">Total notifications: <?= $totalNotifications; ?></span>
        <a class="btn secondary" href="notifications.php" target="_blank">View Public Page</a>
        <a class="btn danger" href="logout.php">Logout</a>
    </div>
</header>

<?php if ($ok = flash('success')): ?><div class="toast success"><?= e($ok); ?></div><?php endif; ?>
<?php if ($err = flash('error')): ?><div class="toast error"><?= e($err); ?></div><?php endif; ?>

<div class="grid two">
    <section class="card form-card">
        <h3><?= $editing ? 'Edit Notification' : 'Add Notification'; ?></h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <input type="hidden" name="action" value="<?= $editing ? 'update_notification' : 'create_notification'; ?>">
            <?php if ($editing): ?><input type="hidden" name="notification_id" value="<?= (int) $editing['id']; ?>"><?php endif; ?>

            <label>Title</label>
            <input type="text" name="title" required value="<?= e($editing['title'] ?? ''); ?>">

            <label>Description</label>
            <textarea name="message" rows="4" required><?= e($editing['message'] ?? ''); ?></textarea>

            <label>Attachment (optional, max 2MB)</label>
            <input type="file" name="attachment">

            <button class="btn" type="submit"><?= $editing ? 'Update' : 'Publish'; ?></button>
            <?php if ($editing): ?><a class="btn secondary" href="dashboard.php">Cancel</a><?php endif; ?>
        </form>
    </section>

    <section class="card form-card">
        <h3>Direct File Upload</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
            <input type="hidden" name="action" value="upload_file">
            <input type="file" name="attachment" required>
            <button class="btn" type="submit">Upload File</button>
        </form>

        <h4>Uploaded Files</h4>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Size</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($uploads as $upload): ?>
                    <tr>
                        <td><a href="<?= e($upload['file_path']); ?>" download><?= e($upload['original_name']); ?></a></td>
                        <td><?= number_format(((int) $upload['file_size']) / 1024, 1); ?> KB</td>
                        <td>
                            <form method="post" onsubmit="return confirm('Delete file?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                                <input type="hidden" name="action" value="delete_upload">
                                <input type="hidden" name="upload_id" value="<?= (int) $upload['id']; ?>">
                                <button class="btn danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="card">
    <div class="list-head">
        <h3>Manage Notifications</h3>
        <form method="get">
            <input type="search" name="search" placeholder="Search title/message" value="<?= e($search); ?>">
            <button class="btn secondary" type="submit">Search</button>
        </form>
    </div>

    <div class="cards">
        <?php foreach ($notifications as $item): ?>
            <article class="card">
                <div class="card-head">
                    <h4><?= e($item['title']); ?></h4>
                    <?php if ($item['updated_at'] !== $item['created_at']): ?>
                        <span class="badge updated">Updated</span>
                    <?php else: ?>
                        <span class="badge new">New</span>
                    <?php endif; ?>
                </div>
                <p><?= nl2br(e($item['message'])); ?></p>
                <?php if (!empty($item['file_path'])): ?>
                    <a href="<?= e($item['file_path']); ?>" download>📎 <?= e($item['original_name']); ?></a>
                <?php endif; ?>
                <small><?= e($item['created_at']); ?></small>
                <div class="row-actions">
                    <a class="btn secondary" href="dashboard.php?edit=<?= (int) $item['id']; ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete notification?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                        <input type="hidden" name="action" value="delete_notification">
                        <input type="hidden" name="notification_id" value="<?= (int) $item['id']; ?>">
                        <button class="btn danger" type="submit">Delete</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/views/partials/footer.php'; ?>

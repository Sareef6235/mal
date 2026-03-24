<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/controllers/NotificationController.php';

$notificationController = new NotificationController($pdo);
$notificationCount = $notificationController->count();
$activePage = 'notification';
$editing = null;

if (isset($_GET['edit'])) {
    $editing = $notificationController->find((int) $_GET['edit']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $isUpdate = $id > 0;

    $result = $isUpdate
        ? $notificationController->update($id, $_POST)
        : $notificationController->store($_POST);

    set_flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect($result['success'] ? app_url('dashboard.php') : ($isUpdate ? app_url('notification.php?edit=' . $id) : app_url('notification.php')));
}

require __DIR__ . '/views/partials/header.php';
require __DIR__ . '/views/partials/flash.php';
?>

<section class="panel">
    <h2><?= $editing ? 'Edit Notification' : 'Create Notification'; ?></h2>
    <form method="POST" class="stack" id="notificationForm">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int) $editing['id']; ?>">
        <?php endif; ?>

        <label for="title">Title</label>
        <input id="title" type="text" name="title" maxlength="255" required value="<?= e($editing['title'] ?? ''); ?>">

        <label for="message">Message (basic rich text/HTML allowed)</label>
        <textarea id="message" name="message" rows="9" required><?= e($editing['message'] ?? ''); ?></textarea>

        <div class="card-actions">
            <button type="submit" class="btn"><?= $editing ? 'Update Notification' : 'Save Notification'; ?></button>
            <?php if ($editing): ?>
                <a class="btn btn-light" href="<?= e(app_url('notification.php')); ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php require __DIR__ . '/views/partials/footer.php'; ?>

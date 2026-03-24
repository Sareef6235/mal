<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/models/Notification.php';

$notifications = Notification::allWithUpload();
$pageTitle = 'Notifications';
require __DIR__ . '/views/partials/header.php';
?>

<header class="topbar">
    <h1>📢 Latest Notifications</h1>
    <a class="btn" href="login.php">Admin Login</a>
</header>

<section class="cards">
    <?php if (!$notifications): ?>
        <div class="card"><p>No notifications yet.</p></div>
    <?php endif; ?>

    <?php foreach ($notifications as $item): ?>
        <article class="card">
            <div class="card-head">
                <h3><?= e($item['title']); ?></h3>
                <?php if (!empty($item['updated_at']) && $item['updated_at'] !== $item['created_at']): ?>
                    <span class="badge updated">Updated</span>
                <?php else: ?>
                    <span class="badge new">New</span>
                <?php endif; ?>
            </div>
            <p><?= nl2br(e($item['message'])); ?></p>
            <small><?= e($item['created_at']); ?></small>
            <?php if (!empty($item['file_path'])): ?>
                <div><a class="link" href="<?= e($item['file_path']); ?>" download>Download: <?= e($item['original_name']); ?></a></div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>

<?php require __DIR__ . '/views/partials/footer.php'; ?>

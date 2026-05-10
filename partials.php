<?php
function render_head(string $title): void { ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> · <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="particle-field">
<?php }

function render_sidebar(string $active = 'dashboard'): void { ?>
<aside class="sidebar">
    <a class="brand" href="index.php"><span class="brand-mark"><i class="bi bi-stars fs-4"></i></span><span><strong class="fs-5">Lumina</strong><small class="d-block text-muted-premium">Study Cloud</small></span></a>
    <?php $items = [
        'dashboard' => ['bi-grid-1x2-fill', 'Dashboard', 'index.php'],
        'materials' => ['bi-journal-richtext', 'All Materials', 'index.php#materials'],
        'subjects' => ['bi-folder2-open', 'Subjects', 'index.php#subjects'],
        'classes' => ['bi-building', 'Classes', 'index.php#classes'],
        'trending' => ['bi-fire', 'Trending', 'index.php#trending'],
        'favorites' => ['bi-star-fill', 'Favorites', 'index.php#favorites'],
        'downloads' => ['bi-download', 'Downloads', 'index.php#downloads'],
        'notifications' => ['bi-bell-fill', 'Notifications', '#'],
        'settings' => ['bi-gear-fill', 'Settings', '#'],
    ]; foreach ($items as $key => $item): ?>
        <a class="nav-pill <?= $active === $key ? 'active' : '' ?>" href="<?= e($item[2]) ?>"><i class="bi <?= e($item[0]) ?>"></i><?= e($item[1]) ?></a>
    <?php endforeach; ?>
    <div class="glass rounded-5 p-3 mt-4">
        <div class="text-uppercase small text-muted-premium fw-bold">Admin Studio</div>
        <p class="small text-muted-premium mb-3">Upload, track, and curate premium resources.</p>
        <a class="btn btn-premium w-100" href="admin.php"><i class="bi bi-cloud-arrow-up"></i> Open Admin</a>
    </div>
</aside>
<?php }

function render_mobile_nav(): void { ?>
<nav class="mobile-bottom-nav glass">
    <a href="index.php"><i class="bi bi-grid-1x2"></i><span>Home</span></a>
    <a href="index.php#materials"><i class="bi bi-journal"></i><span>Files</span></a>
    <a href="index.php#trending"><i class="bi bi-fire"></i><span>Trend</span></a>
    <a href="index.php#favorites"><i class="bi bi-star"></i><span>Saved</span></a>
    <a href="admin.php"><i class="bi bi-person-gear"></i><span>Admin</span></a>
</nav>
<?php }

function render_scripts(): void { ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
<?php }

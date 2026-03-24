<?php
/** @var string $activePage */
/** @var int $notificationCount */
$config = require __DIR__ . '/../../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($config['app']['name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <h1 class="brand"><?= e($config['app']['name']); ?></h1>
        <nav>
            <ul class="nav-menu">
                <li><a class="<?= $activePage === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">Dashboard <span class="badge"><?= (int) $notificationCount; ?></span></a></li>
                <li><a class="<?= $activePage === 'notification' ? 'active' : ''; ?>" href="notification.php">Add Notification</a></li>
                <li><a class="<?= $activePage === 'upload' ? 'active' : ''; ?>" href="upload.php">Upload File</a></li>
            </ul>
        </nav>
    </div>
</header>
<main class="container">

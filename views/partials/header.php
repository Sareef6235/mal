<?php
/** @var string $pageTitle */
$current = basename($_SERVER['PHP_SELF'] ?? '');
$loggedIn = function_exists('is_logged_in') && is_logged_in();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="site-nav">
    <div class="nav-inner">
        <a class="brand" href="notifications.php"><?= e(APP_NAME); ?></a>
        <div class="menu">
            <a class="menu-link <?= $current === 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">Notifications</a>
            <?php if ($loggedIn): ?>
                <a class="menu-link <?= $current === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">Dashboard</a>
                <a class="menu-link" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="menu-link <?= $current === 'login.php' ? 'active' : ''; ?>" href="login.php">Admin Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container">

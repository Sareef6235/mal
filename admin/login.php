<?php
require_once __DIR__ . '/../config.php';

$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, username, password FROM admin WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && hash_equals((string)$admin['password'], $password)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        set_flash('success', 'Welcome admin!');
        redirect('/admin/dashboard.php');
    }

    set_flash('error', 'Invalid admin credentials.');
    redirect('/admin/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Admin Login</h1>
        <nav class="menu"><a href="/index.php">Home</a></nav>
    </div>

    <div class="glass-card" style="max-width:550px; margin: 0 auto;">
        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/login.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="admin" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" value="mhnu123" required>
            </div>
            <button class="btn" type="submit">Login</button>
        </form>
        <p style="margin-top:1rem;">Default: <strong>admin / mhnu123</strong></p>
    </div>
</div>
</body>
</html>

<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/controllers/AuthController.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid request token.');
        redirect('login.php');
    }

    $ok = AuthController::login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''));
    if ($ok) {
        flash('success', 'Welcome back!');
        redirect('dashboard.php');
    }

    flash('error', 'Invalid username or password.');
    redirect('login.php');
}

$pageTitle = 'Admin Login';
require __DIR__ . '/views/partials/header.php';
?>

<div class="auth-card">
    <h2>Admin Login</h2>
    <?php if ($error = flash('error')): ?><div class="toast error"><?= e($error); ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button class="btn" type="submit">Login</button>
    </form>
</div>

<?php require __DIR__ . '/views/partials/footer.php'; ?>

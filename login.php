<?php
require __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        flash('error', 'Invalid login credentials.');
        redirect('/login.php');
    }

    $_SESSION['user_id'] = $user['id'];
    flash('success', 'Welcome back, ' . $user['name'] . '!');
    redirect($user['role'] === 'admin' ? '/admin.php' : '/dashboard.php');
}

$flash = getFlash();
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Malayalam:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/style.css"></head><body><div class="auth-shell"><div class="auth-card card"><?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?><span class="eyebrow">Secure login</span><h1>Admin & customer access</h1><p class="muted">Default admin: <?= e((string) config('security.admin_seed_email')) ?> / <?= e((string) config('security.admin_seed_password')) ?></p><form class="stack-form" method="post"><input name="email" type="email" placeholder="Email Address" required><input name="password" type="password" placeholder="Password" required><button class="primary-btn" type="submit">Login</button></form><p class="muted">New user? <a href="/register.php">Create account</a></p></div></div></body></html>

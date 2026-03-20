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
renderHead('Login', 'Professional customer support system login for customers and admins.');
?>
<body>
<div class="page-shell auth-page">
    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-c"></div>
    <header class="topbar glass compact-topbar" aria-label="Authentication header">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Secure access for customer and admin conversations</p>
            </div>
        </div>
        <nav class="menu" aria-label="Authentication navigation">
            <a href="/">Home</a>
            <a href="/register.php">Create Account</a>
        </nav>
    </header>

    <main class="auth-shell auth-grid">
        <section class="auth-hero card">
            <span class="eyebrow">Secure login</span>
            <h1>Admin &amp; customer access in one clean premium interface</h1>
            <p class="lead">Use the secure login panel to continue your support conversations, manage tickets, and review the latest replies.</p>
            <div class="feature-list compact-list">
                <div class="feature-list-item">Sticky responsive layout</div>
                <div class="feature-list-item">Premium contrast + typography</div>
                <div class="feature-list-item">Optimized for mobile support teams</div>
            </div>
        </section>

        <section class="auth-card card" aria-labelledby="login-title">
            <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
            <span class="eyebrow">Secure login</span>
            <h2 id="login-title">Welcome back</h2>
            <p class="muted">Admin login: <?= e((string) config('security.admin_seed_email')) ?> / <?= e((string) config('security.admin_seed_password')) ?></p>
            <form class="stack-form" method="post" aria-label="Login form">
                <label class="field-label">Email Address
                    <input name="email" type="email" placeholder="Email Address" required aria-label="Email address">
                </label>
                <label class="field-label">Password
                    <input name="password" type="password" placeholder="Password" required aria-label="Password">
                </label>
                <button class="primary-btn" type="submit">Login</button>
            </form>
            <p class="muted">Need a new account? <a href="/register.php">Create account</a></p>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
</body>
</html>

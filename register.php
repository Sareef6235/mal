<?php
require __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        flash('error', 'Please fill all required fields.');
        redirect('/register.php');
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        flash('error', 'Email already registered.');
        redirect('/register.php');
    }

    $insert = db()->prepare('INSERT INTO users (name, email, password_hash, phone, role) VALUES (:name, :email, :password_hash, :phone, :role)');
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'phone' => $phone !== '' ? $phone : null,
        'role' => 'customer',
    ]);

    flash('success', 'Account created successfully. Please login.');
    redirect('/login.php');
}

$flash = getFlash();
renderHead('Register', 'Create a customer account for premium ticket support and chat access.');
?>
<body>
<div class="page-shell auth-page">
    <div class="ambient ambient-b"></div>
    <div class="ambient ambient-c"></div>
    <header class="topbar glass compact-topbar" aria-label="Registration header">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Modern account creation for premium support access</p>
            </div>
        </div>
        <nav class="menu" aria-label="Registration navigation">
            <a href="/">Home</a>
            <a href="/login.php">Login</a>
        </nav>
    </header>

    <main class="auth-shell auth-grid">
        <section class="auth-hero card">
            <span class="eyebrow">Create account</span>
            <h1>Start secure messaging with a premium support experience</h1>
            <p class="lead">Create your customer profile to submit tickets, track updates, and continue conversation history in a clean chat UI.</p>
            <div class="feature-list compact-list">
                <div class="feature-list-item">Order linked messaging</div>
                <div class="feature-list-item">Mobile-first support workspace</div>
                <div class="feature-list-item">Email and WhatsApp ready workflow</div>
            </div>
        </section>

        <section class="auth-card card" aria-labelledby="register-title">
            <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
            <span class="eyebrow">Customer registration</span>
            <h2 id="register-title">Create your account</h2>
            <form class="stack-form" method="post" aria-label="Registration form">
                <label class="field-label">Full Name
                    <input name="name" placeholder="Full Name" required aria-label="Full name">
                </label>
                <label class="field-label">Email Address
                    <input name="email" type="email" placeholder="Email Address" required aria-label="Email address">
                </label>
                <label class="field-label">WhatsApp Number
                    <input name="phone" placeholder="WhatsApp Number" aria-label="WhatsApp number">
                </label>
                <label class="field-label">Password
                    <input name="password" type="password" placeholder="Password" required aria-label="Password">
                </label>
                <button class="primary-btn" type="submit">Create Account</button>
            </form>
            <p class="muted">Already registered? <a href="/login.php">Login now</a></p>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
</body>
</html>

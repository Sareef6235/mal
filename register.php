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
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Register</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Malayalam:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/style.css"></head><body><div class="auth-shell"><div class="auth-card card"><?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?><span class="eyebrow">Create customer account</span><h1>Start secure messaging</h1><form class="stack-form" method="post"><input name="name" placeholder="Full Name" required><input name="email" type="email" placeholder="Email Address" required><input name="phone" placeholder="WhatsApp Number"><input name="password" type="password" placeholder="Password" required><button class="primary-btn" type="submit">Create Account</button></form><p class="muted">Already have an account? <a href="/login.php">Login</a></p></div></div></body></html>

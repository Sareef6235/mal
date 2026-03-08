<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$users = [
    'admin' => ['password' => 'admin123', 'role' => 'admin'],
    'teacher' => ['password' => 'teacher123', 'role' => 'teacher'],
    'student' => ['password' => 'student123', 'role' => 'student'],
];

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request token.';
    } elseif (!isset($users[$username]) || !hash_equals($users[$username]['password'], $password)) {
        $error = 'Invalid credentials.';
    } else {
        $_SESSION['user'] = ['username' => $username, 'role' => $users[$username]['role']];
        header('Location: ' . app_url('dashboard.php'));
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Smart Madrasa Attendance - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="container card">
    <h1>Login</h1>
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <small>Demo: admin/admin123, teacher/teacher123, student/student123</small>
</div>
</body>
</html>

<?php
require __DIR__ . '/config/bootstrap.php';
verify_csrf();
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, username, password, role, name FROM users_view WHERE username=:username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        login_user($user);
        if ($user['role'] === 'admin') header('Location: /admin/dashboard.php');
        elseif ($user['role'] === 'teacher') header('Location: /teacher/dashboard.php');
        else header('Location: /student/dashboard.php');
        exit;
    }
    $error = 'Invalid Login';
}
render_header('Login');
?>
<section class="card"><h1 class="text-xl font-bold">Login</h1><p class="text-sm text-slate-600">Admin: admin / admin1</p>
<?php if ($error): ?><p class="mt-2 rounded bg-red-100 px-2 py-1 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="mt-3 space-y-2"><?= csrf_input() ?><input name="username" class="input" placeholder="Username" required><input name="password" type="password" class="input" placeholder="Password" required><button class="btn btn-primary w-full">Login</button></form>
</section>
<?php render_nav('profile'); render_footer(); ?>

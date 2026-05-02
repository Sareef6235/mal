<?php
require_once 'config.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header('Location: dashboard.php'); exit;
    }
    $error = 'Invalid credentials';
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="style.css" rel="stylesheet"></head>
<body class="d-flex align-items-center justify-content-center">
<div class="card glass p-4" style="width:360px;">
<h4 class="mb-3">Login</h4>
<?php if($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<form method="post">
<input class="form-control mb-2" name="username" placeholder="Username" required>
<input class="form-control mb-3" type="password" name="password" placeholder="Password" required>
<button class="btn btn-primary w-100">Sign In</button>
</form>
</div>
</body></html>

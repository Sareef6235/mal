<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
startSecureSession();
attemptRememberLogin();

if (isset($_SESSION['user_id'])) {
    $user = findUserById((int) $_SESSION['user_id']);
    header('Location: ' . (empty($user['email']) ? 'set_email.php' : 'dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $rememberMe = isset($_POST['remember_me']);

    if (!verifyCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Invalid request token.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } elseif (isLockedOut($username)) {
        $error = 'Too many attempts. Try again in 5 minutes.';
    } else {
        $user = findUserByUsername($username);
        if (!$user || !password_verify($password, $user['password'])) {
            recordLoginFailure($username);
            $error = 'Invalid credentials.';
        } else {
            clearLoginFailures($username);
            completeLogin($user, $rememberMe);
            header('Location: ' . (empty($user['email']) ? 'set_email.php' : 'dashboard.php'));
            exit;
        }
    }
}
?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
  <input name="username" placeholder="Username" required>
  <input type="password" name="password" placeholder="Password" required>
  <label><input type="checkbox" name="remember_me"> Remember me</label>
  <button type="submit">Login</button>
  <p style="color:red;"><?= sanitize($error) ?></p>
</form>
<a href="forgot_password.php">Forgot password?</a>

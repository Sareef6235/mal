<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
startSecureSession();

$userId = (int) ($_SESSION['reset_user_id'] ?? 0);
if ($userId <= 0) {
    header('Location: forgot_password.php');
    exit;
}

$user = findUserById($userId);
if (!$user || (int) $user['reset_verified'] !== 1) {
    header('Location: forgot_password.php');
    exit;
}

$error = '';
$done = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!verifyCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Invalid request token.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!validPasswordPolicy($password)) {
        $error = 'Password must be 6+ chars and include uppercase, lowercase, and number.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE ustads SET password = ?, otp_code = NULL, otp_expires = NULL, reset_verified = 0 WHERE id = ?');
        $stmt->execute([$hash, (int) $user['id']]);
        unset($_SESSION['reset_user'], $_SESSION['reset_user_id'], $_SESSION['otp_attempts']);
        $done = 'Password reset successful. You can login now.';
    }
}
?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
  <input type="password" name="password" placeholder="New password" required>
  <input type="password" name="confirm_password" placeholder="Confirm password" required>
  <button type="submit">Reset Password</button>
</form>
<p style="color:red;"><?= sanitize($error) ?></p>
<p style="color:green;"><?= sanitize($done) ?></p>

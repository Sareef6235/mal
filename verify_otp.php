<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
startSecureSession();

if (empty($_SESSION['reset_user'])) {
    header('Location: forgot_password.php');
    exit;
}

$user = findUserByUsername((string) $_SESSION['reset_user']);
if (!$user) {
    header('Location: forgot_password.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim((string) ($_POST['otp'] ?? ''));
    if (!verifyCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Invalid request token.';
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $error = 'Invalid OTP format.';
    } elseif (($_SESSION['otp_attempts'] ?? 0) >= OTP_MAX_ATTEMPTS) {
        $error = 'Too many OTP attempts.';
    } elseif (empty($user['otp_code']) || empty($user['otp_expires']) || strtotime($user['otp_expires']) < time()) {
        $error = 'OTP expired. Start again.';
    } elseif (!hash_equals((string) $user['otp_code'], $otp)) {
        $_SESSION['otp_attempts'] = (int) ($_SESSION['otp_attempts'] ?? 0) + 1;
        $error = 'Incorrect OTP.';
    } else {
        $stmt = db()->prepare('UPDATE ustads SET reset_verified = 1 WHERE id = ?');
        $stmt->execute([(int) $user['id']]);
        $_SESSION['reset_user_id'] = (int) $user['id'];
        header('Location: reset_password.php');
        exit;
    }
}
?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
  <input name="otp" maxlength="6" placeholder="6-digit OTP" required>
  <button type="submit">Verify OTP</button>
</form>
<p style="color:red;"><?= sanitize($error) ?></p>

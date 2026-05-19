<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
startSecureSession();

$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    if (!verifyCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        $err = 'Invalid request token.';
    } elseif ($username === '') {
        $err = 'Username is required.';
    } else {
        $user = findUserByUsername($username);
        if (!$user) {
            $msg = 'If the username exists, an OTP has been sent.';
        } elseif (empty($user['email'])) {
            $err = 'No recovery email set. Contact admin';
        } else {
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = (new DateTimeImmutable('+' . OTP_EXPIRY_MINUTES . ' minutes'))->format('Y-m-d H:i:s');

            $stmt = db()->prepare('UPDATE ustads SET otp_code = ?, otp_expires = ?, reset_verified = 0 WHERE id = ?');
            $stmt->execute([$otp, $expiresAt, (int) $user['id']]);

            sendOtpEmail($user['email'], $otp);
            $_SESSION['reset_user'] = $user['username'];
            $_SESSION['otp_attempts'] = 0;
            header('Location: verify_otp.php');
            exit;
        }
    }
}
?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
  <input name="username" placeholder="Username" required>
  <button type="submit">Send OTP</button>
</form>
<p style="color:green;"><?= sanitize($msg) ?></p>
<p style="color:red;"><?= sanitize($err) ?></p>

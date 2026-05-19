<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$user = requireAuth();

if (!empty($user['email']) && (int) $user['email_locked'] === 1) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    if (!verifyCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Invalid request token.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $check = db()->prepare('SELECT id FROM ustads WHERE email = ? AND id <> ? LIMIT 1');
        $check->execute([$email, (int) $user['id']]);

        if ($check->fetch()) {
            $error = 'Email already in use.';
        } else {
            $stmt = db()->prepare('UPDATE ustads SET email = ?, email_locked = 1 WHERE id = ?');
            $stmt->execute([$email, (int) $user['id']]);
            $message = 'Email set successfully.';
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<h2>Set Recovery Email</h2>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
  <input type="email" name="email" required placeholder="Email">
  <button type="submit">Save Email</button>
</form>
<p style="color:green;"><?= sanitize($message) ?></p>
<p style="color:red;"><?= sanitize($error) ?></p>

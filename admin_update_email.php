<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAdmin();

$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $email = trim((string) ($_POST['email'] ?? ''));

    if (!verifyCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        $err = 'Invalid request token.';
    } elseif ($id <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Invalid data.';
    } else {
        $stmt = db()->prepare('UPDATE ustads SET email = ?, email_locked = 0 WHERE id = ?');
        $stmt->execute([$email, $id]);
        $msg = 'Email updated by admin.';
    }
}
?>
<form method="post">
  <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
  <input type="number" name="id" placeholder="User ID" required>
  <input type="email" name="email" placeholder="New email" required>
  <button type="submit">Admin Update Email</button>
</form>
<p style="color:green;"><?= sanitize($msg) ?></p>
<p style="color:red;"><?= sanitize($err) ?></p>

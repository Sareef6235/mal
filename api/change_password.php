<?php
require __DIR__ . '/../config.php';
$userId = require_auth();
verify_csrf();

if (!rate_limit('password_change', 5, 60)) {
    json_response(['success' => false, 'message' => 'Password change rate limit reached. Try again shortly.'], 429);
}

$current = (string) ($_POST['current_password'] ?? '');
$new = (string) ($_POST['new_password'] ?? '');
$confirm = (string) ($_POST['confirm_password'] ?? '');

if (strlen($new) < 10 || !preg_match('/[A-Z]/', $new) || !preg_match('/\d/', $new)) {
    json_response(['success' => false, 'message' => 'Use at least 10 characters with an uppercase letter and number.'], 422);
}
if (!hash_equals($new, $confirm)) {
    json_response(['success' => false, 'message' => 'New password confirmation does not match.'], 422);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($current, $user['password_hash'])) {
        json_response(['success' => false, 'message' => 'Current password is incorrect.'], 422);
    }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
    $update->execute(['hash' => $hash, 'id' => $userId]);
} catch (Throwable $exception) {
    if (env_value('DEMO_AUTH', 'true') !== 'true') {
        json_response(['success' => false, 'message' => 'Database error while changing password.'], 500);
    }
}

json_response(['success' => true, 'message' => 'Password changed securely.']);

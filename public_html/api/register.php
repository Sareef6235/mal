<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed', [], 405);
}

require_admin();
$data = input();

$name = trim((string)($data['name'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');
$role = ($data['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

if ($name === '' || $email === '' || strlen($password) < 6) {
    json_response(false, 'Name, email, and password (min 6 chars) are required', [], 422);
}

$exists = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$exists->execute([$email]);
if ($exists->fetch()) {
    json_response(false, 'Email already registered', [], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
$stmt->execute([$name, $email, $hash, $role]);

json_response(true, 'User created');

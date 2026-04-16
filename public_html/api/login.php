<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed', [], 405);
}

$data = input();
$email = trim((string)($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(false, 'Email and password are required', [], 422);
}

$stmt = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(false, 'Invalid credentials', [], 401);
}

$_SESSION['user'] = [
    'id' => (int)$user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
];

json_response(true, 'Login successful', ['user' => $_SESSION['user']]);

<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed', [], 405);
}

$data = input();
$action = (string)($data['action'] ?? '');

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    json_response(true, 'Logged out');
}

if ($action === 'delete_user') {
    $admin = require_admin();
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0 || $id === (int)$admin['id']) {
        json_response(false, 'Invalid user', [], 422);
    }
    $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    json_response(true, 'User deleted');
}

json_response(false, 'Invalid action', [], 422);

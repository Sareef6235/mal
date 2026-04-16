<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, 'Method not allowed', [], 405);
}

$user = require_auth();

$itemsStmt = db()->query('SELECT i.id, i.title, i.status, i.due_date, i.sort_order, i.updated_at, i.category_id, c.name AS category_name FROM items i JOIN categories c ON c.id = i.category_id ORDER BY i.sort_order ASC, i.id DESC');
$items = $itemsStmt->fetchAll();

$categoriesStmt = db()->query('SELECT id, name FROM categories ORDER BY name');
$categories = $categoriesStmt->fetchAll();

$users = [];
if (($user['role'] ?? 'user') === 'admin') {
    $users = db()->query('SELECT id, name, email, role FROM users ORDER BY id DESC')->fetchAll();
}

json_response(true, 'Data loaded', [
    'user' => $user,
    'items' => $items,
    'categories' => $categories,
    'users' => $users,
]);

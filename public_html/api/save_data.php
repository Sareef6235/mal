<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed', [], 405);
}

require_auth();
$data = input();
$action = (string)($data['action'] ?? '');

if ($action === 'reorder') {
    $ids = $data['ids'] ?? [];
    if (!is_array($ids)) {
        json_response(false, 'Invalid payload', [], 422);
    }
    $stmt = db()->prepare('UPDATE items SET sort_order = ?, updated_at = NOW() WHERE id = ?');
    foreach ($ids as $idx => $id) {
        $stmt->execute([$idx + 1, (int)$id]);
    }
    json_response(true, 'Order updated');
}

$title = trim((string)($data['title'] ?? ''));
$categoryId = (int)($data['category_id'] ?? 0);
$status = (string)($data['status'] ?? 'pending');
$dueDate = (string)($data['due_date'] ?? '');

if ($title === '' || $categoryId <= 0 || $dueDate === '') {
    json_response(false, 'Missing required fields', [], 422);
}

$max = db()->query('SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM items')->fetch();
$nextOrder = (int)$max['next_order'];

$stmt = db()->prepare('INSERT INTO items (title, category_id, status, due_date, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
$stmt->execute([$title, $categoryId, $status, $dueDate, $nextOrder]);

json_response(true, 'Item created');

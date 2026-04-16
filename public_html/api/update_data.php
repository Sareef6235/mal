<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed', [], 405);
}

require_auth();
$data = input();
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    json_response(false, 'Invalid ID', [], 422);
}

if (isset($data['field'], $data['value'])) {
    $allowed = ['title', 'status', 'due_date'];
    $field = (string)$data['field'];
    if (!in_array($field, $allowed, true)) {
        json_response(false, 'Invalid field', [], 422);
    }
    $stmt = db()->prepare("UPDATE items SET {$field} = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([(string)$data['value'], $id]);
    json_response(true, 'Item updated');
}

$title = trim((string)($data['title'] ?? ''));
$categoryId = (int)($data['category_id'] ?? 0);
$status = (string)($data['status'] ?? 'pending');
$dueDate = (string)($data['due_date'] ?? '');

if ($title === '' || $categoryId <= 0 || $dueDate === '') {
    json_response(false, 'Missing required fields', [], 422);
}

$stmt = db()->prepare('UPDATE items SET title = ?, category_id = ?, status = ?, due_date = ?, updated_at = NOW() WHERE id = ?');
$stmt->execute([$title, $categoryId, $status, $dueDate, $id]);

json_response(true, 'Item updated');

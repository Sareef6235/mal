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

$stmt = db()->prepare('DELETE FROM items WHERE id = ?');
$stmt->execute([$id]);
json_response(true, 'Item deleted');

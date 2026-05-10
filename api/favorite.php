<?php
require_once __DIR__ . '/../functions.php';
verify_csrf();
$materialId = (int)($_POST['material_id'] ?? 0);
if ($materialId <= 0) {
    json_response(['ok' => false, 'message' => 'Invalid material.'], 422);
}
$sessionId = session_id();
$userId = $_SESSION['user_id'] ?? null;
$stmt = $pdo->prepare('SELECT id FROM favorites WHERE material_id = ? AND (user_id <=> ? OR session_id = ?) LIMIT 1');
$stmt->execute([$materialId, $userId, $sessionId]);
$existing = $stmt->fetchColumn();
if ($existing) {
    $pdo->prepare('DELETE FROM favorites WHERE id = ?')->execute([$existing]);
    json_response(['ok' => true, 'message' => 'Removed from favorites.']);
}
$stmt = $pdo->prepare('INSERT INTO favorites (material_id, user_id, session_id) VALUES (?, ?, ?)');
$stmt->execute([$materialId, $userId, $sessionId]);
json_response(['ok' => true, 'message' => 'Saved to favorites.']);

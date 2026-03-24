<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('Method not allowed');

    $ticketId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_POST['ticket_id'] ?? 'global'));
    $userId = (int)($_POST['user_id'] ?? 0);
    $message = trim((string)($_POST['message'] ?? ''));

    if ($userId <= 0 || $message === '') throw new RuntimeException('Invalid input');
    if (mb_strlen($message) > 2000) throw new RuntimeException('Message too long');

    $pdo = db();
    $sql = 'INSERT INTO messages (ticket_id, user_id, message, seen) VALUES (:ticket_id, :user_id, :message, 0)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ticket_id' => $ticketId, ':user_id' => $userId, ':message' => $message]);

    echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}

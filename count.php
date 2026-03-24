<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

try {
    $ticketId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_GET['ticket_id'] ?? 'global'));
    $stmt = db()->prepare('SELECT COUNT(*) AS total, SUM(CASE WHEN seen = 0 THEN 1 ELSE 0 END) AS unseen FROM messages WHERE ticket_id = :ticket_id');
    $stmt->execute([':ticket_id' => $ticketId]);
    $row = $stmt->fetch() ?: ['total' => 0, 'unseen' => 0];

    echo json_encode(['ok' => true, 'total' => (int)$row['total'], 'unseen' => (int)$row['unseen']]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}

<?php
declare(strict_types=1);
header('Content-Type: application/json');

function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_NAME') ?: 'chat_app');
    $pdo = new PDO($dsn, getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

try {
    $ticketId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_GET['ticket_id'] ?? 'global'));
    $sinceId = (int)($_GET['since_id'] ?? 0);

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, ticket_id, user_id, message, seen, created_at FROM messages WHERE ticket_id = :ticket_id AND id > :since_id ORDER BY id ASC LIMIT 200');
    $stmt->bindValue(':ticket_id', $ticketId, PDO::PARAM_STR);
    $stmt->bindValue(':since_id', $sinceId, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'messages' => $rows]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}

<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');

function table_exists_send(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute(['table_name' => $table]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $exception) {
        return false;
    }
}

function table_columns_send(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        return array_map(static fn (array $row): string => (string) ($row['Field'] ?? ''), $rows);
    } catch (Throwable $exception) {
        return [];
    }
}

function first_existing_column_send(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function resolve_message_source_send(PDO $pdo): ?array
{
    foreach (['ticket_messages', 'messages'] as $table) {
        if (!table_exists_send($pdo, $table)) {
            continue;
        }

        $columns = table_columns_send($pdo, $table);
        $map = [
            'table' => $table,
            'ticket_id_column' => first_existing_column_send($columns, ['ticket_id']),
            'message_column' => first_existing_column_send($columns, ['message', 'body', 'content', 'text']),
            'id_column' => first_existing_column_send($columns, ['id']),
            'user_id_column' => first_existing_column_send($columns, ['user_id']),
            'source_column' => first_existing_column_send($columns, ['source']),
            'sender_role_column' => first_existing_column_send($columns, ['sender_role']),
            'is_read_column' => first_existing_column_send($columns, ['is_read']),
            'created_at_column' => first_existing_column_send($columns, ['created_at', 'sent_at', 'updated_at']),
            'seen_at_column' => first_existing_column_send($columns, ['seen_at']),
            'seen_by_admin_at_column' => first_existing_column_send($columns, ['seen_by_admin_at']),
            'seen_by_customer_at_column' => first_existing_column_send($columns, ['seen_by_customer_at', 'seen_by_user_at']),
            'delivered_at_column' => first_existing_column_send($columns, ['delivered_at']),
            'delivered_to_admin_at_column' => first_existing_column_send($columns, ['delivered_to_admin_at']),
            'delivered_to_customer_at_column' => first_existing_column_send($columns, ['delivered_to_customer_at', 'delivered_to_user_at']),
        ];
        if ($map['ticket_id_column'] && $map['message_column']) {
            return $map;
        }
    }

    return null;
}

function request_payload_send(): array
{
    $raw = (string) file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    return $_POST;
}

function viewer_role_send(array $user): string
{
    return (($user['role'] ?? '') === 'admin') ? 'admin' : 'user';
}

function viewer_customer_column_send(array $source): ?string
{
    return $source['seen_by_customer_at_column'] ?? null;
}

function ticket_accessible_send(int $ticketId, array $user): bool
{
    if (function_exists('ticketWithMessages')) {
        return (bool) ticketWithMessages($ticketId, $user);
    }

    return (($user['role'] ?? '') === 'admin');
}

function update_ticket_presence_send(PDO $pdo, int $ticketId, string $viewerRole, bool $isTyping): void
{
    if (!table_exists_send($pdo, 'tickets')) {
        return;
    }

    $columns = table_columns_send($pdo, 'tickets');
    $sets = [];

    $presenceColumn = $viewerRole === 'admin'
        ? first_existing_column_send($columns, ['admin_last_active_at'])
        : first_existing_column_send($columns, ['customer_last_active_at', 'user_last_active_at']);

    if ($presenceColumn !== null) {
        $sets[] = "`{$presenceColumn}` = CURRENT_TIMESTAMP";
    }

    $typingRoleColumn = first_existing_column_send($columns, ['typing_role']);
    $typingUpdatedColumn = first_existing_column_send($columns, ['typing_updated_at']);

    if ($typingRoleColumn !== null) {
        $sets[] = "`{$typingRoleColumn}` = " . ($isTyping ? $pdo->quote($viewerRole) : 'NULL');
    }
    if ($typingUpdatedColumn !== null) {
        $sets[] = "`{$typingUpdatedColumn}` = " . ($isTyping ? 'CURRENT_TIMESTAMP' : 'NULL');
    }

    if ($sets === []) {
        return;
    }

    $sql = 'UPDATE `tickets` SET ' . implode(', ', $sets) . ' WHERE `id` = :ticket_id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['ticket_id' => $ticketId]);
}

function update_ticket_after_message_send(PDO $pdo, array $source, int $ticketId, string $message, string $senderRole): void
{
    if (!table_exists_send($pdo, 'tickets')) {
        return;
    }

    $columns = table_columns_send($pdo, 'tickets');
    $sets = [];
    $params = ['ticket_id' => $ticketId];

    if (in_array('last_message_at', $columns, true)) {
        $sets[] = '`last_message_at` = CURRENT_TIMESTAMP';
    }
    if (in_array('last_message_preview', $columns, true)) {
        $sets[] = '`last_message_preview` = :last_message_preview';
        $params['last_message_preview'] = mb_strimwidth(trim($message), 0, 255, '...');
    }
    if (in_array('admin_unread_count', $columns, true)) {
        $sets[] = $senderRole === 'admin' ? '`admin_unread_count` = 0' : '`admin_unread_count` = COALESCE(`admin_unread_count`, 0) + 1';
    }
    if (in_array('customer_unread_count', $columns, true)) {
        $sets[] = $senderRole === 'admin' ? '`customer_unread_count` = COALESCE(`customer_unread_count`, 0) + 1' : '`customer_unread_count` = 0';
    }

    $typingRoleColumn = first_existing_column_send($columns, ['typing_role']);
    if ($typingRoleColumn !== null) {
        $sets[] = "`{$typingRoleColumn}` = NULL";
    }
    $typingUpdatedColumn = first_existing_column_send($columns, ['typing_updated_at']);
    if ($typingUpdatedColumn !== null) {
        $sets[] = "`{$typingUpdatedColumn}` = NULL";
    }

    if ($sets === []) {
        return;
    }

    $sql = 'UPDATE `tickets` SET ' . implode(', ', $sets) . ' WHERE `id` = :ticket_id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function log_notification_event_send(PDO $pdo, int $ticketId, ?int $messageId, string $eventType, array $payload): void
{
    if (!table_exists_send($pdo, 'notification_events')) {
        return;
    }

    $columns = table_columns_send($pdo, 'notification_events');
    $eventTypeColumn = first_existing_column_send($columns, ['event_type']);
    if ($eventTypeColumn === null) {
        return;
    }

    $insertColumns = [$eventTypeColumn];
    $placeholders = [':event_type'];
    $params = ['event_type' => $eventType];

    if (in_array('ticket_id', $columns, true)) {
        $insertColumns[] = 'ticket_id';
        $placeholders[] = ':ticket_id';
        $params['ticket_id'] = $ticketId;
    }
    if (in_array('message_id', $columns, true)) {
        $insertColumns[] = 'message_id';
        $placeholders[] = ':message_id';
        $params['message_id'] = $messageId;
    }
    if (in_array('channel', $columns, true)) {
        $insertColumns[] = 'channel';
        $placeholders[] = ':channel';
        $params['channel'] = 'system';
    }
    if (in_array('payload', $columns, true)) {
        $insertColumns[] = 'payload';
        $placeholders[] = ':payload';
        $params['payload'] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $sql = 'INSERT INTO `notification_events` (`' . implode('`,`', $insertColumns) . '`) VALUES (' . implode(',', $placeholders) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

try {
    $user = requireLogin();
    $payload = request_payload_send();
    $action = trim((string) ($payload['action'] ?? 'send'));
    $ticketId = (int) ($payload['ticket_id'] ?? 0);

    if ($ticketId <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'Ticket is required.']);
        exit;
    }

    if (!ticket_accessible_send($ticketId, $user)) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found.']);
        exit;
    }

    $pdo = db();
    $viewerRole = viewer_role_send($user);

    if ($action === 'typing') {
        update_ticket_presence_send($pdo, $ticketId, $viewerRole, (bool) ($payload['is_typing'] ?? false));
        log_notification_event_send($pdo, $ticketId, null, 'typing', [
            'role' => $viewerRole,
            'is_typing' => (bool) ($payload['is_typing'] ?? false),
        ]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $message = trim((string) ($payload['message'] ?? ''));
    if ($message === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Message is required.']);
        exit;
    }

    $source = resolve_message_source_send($pdo);

    if (function_exists('createMessage')) {
        createMessage($ticketId, (int) ($user['id'] ?? 0), $message, $viewerRole === 'admin' ? 'admin' : 'web');
        update_ticket_presence_send($pdo, $ticketId, $viewerRole, false);
        if ($source !== null) {
            update_ticket_after_message_send($pdo, $source, $ticketId, $message, $viewerRole);
        }
        log_notification_event_send($pdo, $ticketId, null, 'message_sent', [
            'role' => $viewerRole,
            'message' => $message,
        ]);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($source === null) {
        http_response_code(500);
        echo json_encode(['error' => 'No writable message table found.']);
        exit;
    }

    $columns = [$source['ticket_id_column'], $source['message_column']];
    $params = ['ticket_id' => $ticketId, 'message_text' => $message];
    $placeholders = [':ticket_id', ':message_text'];

    if ($source['user_id_column']) {
        $columns[] = $source['user_id_column'];
        $placeholders[] = ':user_id';
        $params['user_id'] = (int) ($user['id'] ?? 0) ?: null;
    }
    if ($source['source_column']) {
        $columns[] = $source['source_column'];
        $placeholders[] = ':source';
        $params['source'] = $viewerRole === 'admin' ? 'admin' : 'web';
    }
    if ($source['sender_role_column']) {
        $columns[] = $source['sender_role_column'];
        $placeholders[] = ':sender_role';
        $params['sender_role'] = $viewerRole;
    }
    if ($source['is_read_column']) {
        $columns[] = $source['is_read_column'];
        $placeholders[] = ':is_read';
        $params['is_read'] = 0;
    }
    if ($source['delivered_at_column']) {
        $columns[] = $source['delivered_at_column'];
        $placeholders[] = 'CURRENT_TIMESTAMP';
    }
    if ($viewerRole === 'admin' && $source['delivered_to_customer_at_column']) {
        $columns[] = $source['delivered_to_customer_at_column'];
        $placeholders[] = 'CURRENT_TIMESTAMP';
    }
    if ($viewerRole === 'user' && $source['delivered_to_admin_at_column']) {
        $columns[] = $source['delivered_to_admin_at_column'];
        $placeholders[] = 'CURRENT_TIMESTAMP';
    }

    $sql = 'INSERT INTO `' . $source['table'] . '` (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', $placeholders) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messageId = (int) $pdo->lastInsertId();

    update_ticket_presence_send($pdo, $ticketId, $viewerRole, false);
    update_ticket_after_message_send($pdo, $source, $ticketId, $message, $viewerRole);
    log_notification_event_send($pdo, $ticketId, $messageId, 'message_sent', [
        'role' => $viewerRole,
        'message' => $message,
    ]);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to send message.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');

const CHAT_ATTACHMENT_TOKEN_PATTERN = '/\[\[attachment:(.*?)\]\]/';

function table_exists_chat(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute(['table_name' => $table]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $exception) {
        return false;
    }
}

function table_columns_chat(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        return array_map(static fn (array $row): string => (string) ($row['Field'] ?? ''), $rows);
    } catch (Throwable $exception) {
        return [];
    }
}

function first_existing_column_chat(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function resolve_message_source_chat(PDO $pdo): ?array
{
    foreach (['ticket_messages', 'messages'] as $table) {
        if (!table_exists_chat($pdo, $table)) {
            continue;
        }

        $columns = table_columns_chat($pdo, $table);
        $map = [
            'table' => $table,
            'ticket_id_column' => first_existing_column_chat($columns, ['ticket_id']),
            'message_column' => first_existing_column_chat($columns, ['message', 'body', 'content', 'text']),
            'created_at_column' => first_existing_column_chat($columns, ['created_at', 'sent_at', 'updated_at']),
            'id_column' => first_existing_column_chat($columns, ['id']),
            'is_read_column' => first_existing_column_chat($columns, ['is_read']),
            'sender_role_column' => first_existing_column_chat($columns, ['sender_role']),
            'user_id_column' => first_existing_column_chat($columns, ['user_id']),
            'source_column' => first_existing_column_chat($columns, ['source']),
            'seen_at_column' => first_existing_column_chat($columns, ['seen_at']),
            'seen_by_admin_at_column' => first_existing_column_chat($columns, ['seen_by_admin_at']),
            'seen_by_customer_at_column' => first_existing_column_chat($columns, ['seen_by_customer_at', 'seen_by_user_at']),
            'delivered_at_column' => first_existing_column_chat($columns, ['delivered_at']),
            'delivered_to_admin_at_column' => first_existing_column_chat($columns, ['delivered_to_admin_at']),
            'delivered_to_customer_at_column' => first_existing_column_chat($columns, ['delivered_to_customer_at', 'delivered_to_user_at']),
        ];

        if ($map['ticket_id_column'] && $map['message_column'] && $map['created_at_column'] && $map['id_column']) {
            return $map;
        }
    }

    return null;
}

function viewer_role_chat(array $user): string
{
    return (($user['role'] ?? '') === 'admin') ? 'admin' : 'user';
}

function ticket_accessible_chat(int $ticketId, array $user): bool
{
    if (function_exists('ticketWithMessages')) {
        return (bool) ticketWithMessages($ticketId, $user);
    }

    return (($user['role'] ?? '') === 'admin');
}

function extract_attachment_tokens_chat(string $message): array
{
    preg_match_all(CHAT_ATTACHMENT_TOKEN_PATTERN, $message, $matches);
    $attachments = [];

    foreach ($matches[1] ?? [] as $token) {
        $decoded = json_decode(base64_decode((string) $token, true) ?: '', true);
        if (is_array($decoded) && isset($decoded['path'], $decoded['type'])) {
            $attachments[] = $decoded;
        }
    }

    $cleanMessage = trim((string) preg_replace(CHAT_ATTACHMENT_TOKEN_PATTERN, '', $message));

    return [$cleanMessage, $attachments];
}

function other_role_chat(string $viewerRole): string
{
    return $viewerRole === 'admin' ? 'user' : 'admin';
}

function update_ticket_presence_chat(PDO $pdo, int $ticketId, string $viewerRole): void
{
    if (!table_exists_chat($pdo, 'tickets')) {
        return;
    }

    $columns = table_columns_chat($pdo, 'tickets');
    $sets = [];
    $presenceColumn = $viewerRole === 'admin'
        ? first_existing_column_chat($columns, ['admin_last_active_at'])
        : first_existing_column_chat($columns, ['customer_last_active_at', 'user_last_active_at']);

    if ($presenceColumn !== null) {
        $sets[] = "`{$presenceColumn}` = CURRENT_TIMESTAMP";
    }

    if ($sets === []) {
        return;
    }

    $sql = 'UPDATE `tickets` SET ' . implode(', ', $sets) . ' WHERE `id` = :ticket_id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['ticket_id' => $ticketId]);
}

function mark_messages_seen_chat(PDO $pdo, array $source, int $ticketId, string $viewerRole): void
{
    $sets = [];
    $params = ['ticket_id' => $ticketId];

    if ($source['is_read_column']) {
        $sets[] = "`{$source['is_read_column']}` = 1";
    }
    if ($source['seen_at_column']) {
        $sets[] = "`{$source['seen_at_column']}` = CURRENT_TIMESTAMP";
    }

    $viewerSeenColumn = $viewerRole === 'admin'
        ? ($source['seen_by_admin_at_column'] ?? null)
        : ($source['seen_by_customer_at_column'] ?? null);

    if ($viewerSeenColumn) {
        $sets[] = "`{$viewerSeenColumn}` = CURRENT_TIMESTAMP";
    }

    if ($sets === []) {
        return;
    }

    $conditions = ["`{$source['ticket_id_column']}` = :ticket_id"];
    if ($source['sender_role_column']) {
        $conditions[] = "`{$source['sender_role_column']}` = :sender_role";
        $params['sender_role'] = other_role_chat($viewerRole);
    } elseif ($source['source_column']) {
        $conditions[] = $viewerRole === 'admin'
            ? "COALESCE(`{$source['source_column']}`, 'web') <> 'admin'"
            : "COALESCE(`{$source['source_column']}`, 'web') = 'admin'";
    }

    $guardColumn = $viewerSeenColumn ?: ($source['is_read_column'] ?: $source['seen_at_column']);
    if ($guardColumn) {
        $conditions[] = "(`{$guardColumn}` IS NULL OR `{$guardColumn}` = 0)";
    }

    $sql = 'UPDATE `' . $source['table'] . '` SET ' . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $conditions);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if (!table_exists_chat($pdo, 'tickets')) {
        return;
    }

    $ticketColumns = table_columns_chat($pdo, 'tickets');
    $unreadColumn = $viewerRole === 'admin'
        ? first_existing_column_chat($ticketColumns, ['admin_unread_count'])
        : first_existing_column_chat($ticketColumns, ['customer_unread_count']);

    if ($unreadColumn !== null) {
        $ticketStmt = $pdo->prepare('UPDATE `tickets` SET `' . $unreadColumn . '` = 0 WHERE `id` = :ticket_id');
        $ticketStmt->execute(['ticket_id' => $ticketId]);
    }
}

function compute_seen_at_chat(array $message, string $senderRole): ?string
{
    if ($senderRole === 'admin') {
        return $message['seen_by_customer_at'] ?? $message['seen_by_user_at'] ?? $message['seen_at'] ?? null;
    }

    return $message['seen_by_admin_at'] ?? $message['seen_at'] ?? null;
}

function compute_delivered_at_chat(array $message, string $senderRole): ?string
{
    if ($senderRole === 'admin') {
        return $message['delivered_to_customer_at'] ?? $message['delivered_to_user_at'] ?? $message['delivered_at'] ?? null;
    }

    return $message['delivered_to_admin_at'] ?? $message['delivered_at'] ?? null;
}

function normalize_sender_role_chat(array $message): string
{
    $role = strtolower(trim((string) ($message['role'] ?? $message['sender_role'] ?? '')));
    if ($role === 'admin') {
        return 'admin';
    }

    return 'user';
}

function normalize_sender_name_chat(array $message, array $ticket, string $senderRole): string
{
    foreach (['name', 'user_name', 'sender_name', 'full_name', 'display_name', 'username'] as $field) {
        $value = trim((string) ($message[$field] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    $email = trim((string) ($message['email'] ?? ''));
    if ($email !== '') {
        return $email;
    }

    return $senderRole === 'admin' ? 'Admin' : (trim((string) ($ticket['customer_name'] ?? $ticket['name'] ?? 'Customer')) ?: 'Customer');
}

function normalize_ticket_message_chat(array $message, array $ticket, string $viewerRole): array
{
    [$cleanMessage, $attachments] = extract_attachment_tokens_chat((string) ($message['message'] ?? $message['message_text'] ?? ''));
    $senderRole = normalize_sender_role_chat($message);
    $messageText = $cleanMessage !== '' ? $cleanMessage : ($attachments !== [] ? 'Attachment shared.' : '');
    $seenAt = compute_seen_at_chat($message, $senderRole);
    $deliveredAt = compute_delivered_at_chat($message, $senderRole);
    $sentByViewer = $senderRole === $viewerRole;
    $seenLabel = $sentByViewer && $seenAt ? '👁️ Seen ✔✔ system' : ($sentByViewer && $deliveredAt ? 'Delivered ✔' : '');

    return [
        'id' => (int) ($message['id'] ?? 0),
        'message_text' => $messageText,
        'raw_message_text' => (string) ($message['message'] ?? $message['message_text'] ?? ''),
        'created_at' => (string) ($message['created_at'] ?? ''),
        'sender_role' => $senderRole,
        'sender_name' => normalize_sender_name_chat($message, $ticket, $senderRole),
        'is_read' => (int) ($message['is_read'] ?? ($seenAt ? 1 : 0)),
        'seen_at' => $seenAt,
        'delivered_at' => $deliveredAt,
        'seen_label' => $seenLabel,
        'attachments' => $attachments,
        'has_attachments' => $attachments !== [],
        'sent_by_viewer' => $sentByViewer,
    ];
}

function ticket_presence_chat(PDO $pdo, int $ticketId, string $viewerRole): array
{
    if (!table_exists_chat($pdo, 'tickets')) {
        return [
            'self_online' => true,
            'other_online' => false,
            'typing_role' => null,
            'typing_label' => '',
            'typing_active' => false,
            'connection_mode' => 'polling',
        ];
    }

    $columns = table_columns_chat($pdo, 'tickets');
    $selects = ['id'];
    foreach (['typing_role', 'typing_updated_at', 'admin_last_active_at', 'customer_last_active_at', 'user_last_active_at'] as $column) {
        if (in_array($column, $columns, true)) {
            $selects[] = $column;
        }
    }

    $sql = 'SELECT ' . implode(', ', $selects) . ' FROM `tickets` WHERE `id` = :ticket_id LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['ticket_id' => $ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $otherPresenceAt = $viewerRole === 'admin'
        ? ($ticket['customer_last_active_at'] ?? $ticket['user_last_active_at'] ?? null)
        : ($ticket['admin_last_active_at'] ?? null);

    $typingRole = (string) ($ticket['typing_role'] ?? '');
    $typingUpdatedAt = strtotime((string) ($ticket['typing_updated_at'] ?? '')) ?: 0;
    $typingActive = $typingRole !== '' && $typingRole !== $viewerRole && $typingUpdatedAt >= (time() - 7);
    $otherOnline = (($otherPresenceAt ? strtotime((string) $otherPresenceAt) : 0) ?: 0) >= (time() - 20);

    return [
        'self_online' => true,
        'other_online' => $otherOnline,
        'typing_role' => $typingActive ? $typingRole : null,
        'typing_label' => $typingActive ? ((($typingRole === 'admin') ? 'Admin' : 'Customer') . ' is typing…') : '',
        'typing_active' => $typingActive,
        'connection_mode' => 'polling',
    ];
}

function fetch_ticket_row_chat(PDO $pdo, int $ticketId, array $user): ?array
{
    $sql = 'SELECT t.*, COALESCE(u.name, "Unknown User") AS customer_name, COALESCE(u.email, "") AS customer_email FROM tickets t LEFT JOIN users u ON u.id = t.user_id WHERE t.id = :id';
    $params = ['id' => $ticketId];
    if (($user['role'] ?? '') !== 'admin') {
        $sql .= ' AND t.user_id = :user_id';
        $params['user_id'] = (int) ($user['id'] ?? 0);
    }
    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    return $ticket ?: null;
}

try {
    $user = requireLogin();
    $ticketId = (int) ($_GET['id'] ?? 0);
    if ($ticketId <= 0) {
        http_response_code(422);
        echo json_encode(['error' => 'Invalid ticket']);
        exit;
    }

    if (!ticket_accessible_chat($ticketId, $user)) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        exit;
    }

    $viewerRole = viewer_role_chat($user);
    $pdo = db();
    $source = resolve_message_source_chat($pdo);

    update_ticket_presence_chat($pdo, $ticketId, $viewerRole);
    if ($source !== null) {
        mark_messages_seen_chat($pdo, $source, $ticketId, $viewerRole);
    }

    if (function_exists('ticketWithMessages')) {
        $ticket = ticketWithMessages($ticketId, $user);
        if ($ticket) {
            $messages = [];
            foreach ((array) ($ticket['messages'] ?? []) as $message) {
                $messages[] = normalize_ticket_message_chat((array) $message, (array) $ticket, $viewerRole);
            }

            echo json_encode([
                'ticket' => $ticket,
                'messages' => $messages,
                'viewer_role' => $viewerRole,
                'presence' => ticket_presence_chat($pdo, $ticketId, $viewerRole),
                'meta' => [
                    'latest_message_id' => (int) (($messages[array_key_last($messages)]['id'] ?? 0)),
                    'unread_count' => count(array_filter($messages, static fn (array $message): bool => $message['sender_role'] !== $viewerRole && empty($message['seen_at']))),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    if ($source === null) {
        echo json_encode(['messages' => [], 'ticket' => null, 'viewer_role' => $viewerRole], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $ticket = fetch_ticket_row_chat($pdo, $ticketId, $user);
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket not found']);
        exit;
    }

    $selectSenderRole = $source['sender_role_column']
        ? "m.`{$source['sender_role_column']}`"
        : ($source['source_column'] ? "CASE WHEN COALESCE(m.`{$source['source_column']}`, 'web') = 'admin' THEN 'admin' ELSE 'user' END" : "CASE WHEN COALESCE(sender.role, 'user') = 'admin' THEN 'admin' ELSE 'user' END");

    $userJoin = $source['user_id_column'] ? ' LEFT JOIN users sender ON sender.id = m.`' . $source['user_id_column'] . '`' : '';
    $senderName = $source['user_id_column'] ? 'COALESCE(sender.name, CASE WHEN ' . $selectSenderRole . ' = "admin" THEN "Admin" ELSE "Customer" END)' : 'CASE WHEN ' . $selectSenderRole . ' = "admin" THEN "Admin" ELSE "Customer" END';

    $extraSelects = [];
    foreach ([
        'seen_at_column' => 'seen_at',
        'seen_by_admin_at_column' => 'seen_by_admin_at',
        'seen_by_customer_at_column' => 'seen_by_customer_at',
        'delivered_at_column' => 'delivered_at',
        'delivered_to_admin_at_column' => 'delivered_to_admin_at',
        'delivered_to_customer_at_column' => 'delivered_to_customer_at',
    ] as $key => $alias) {
        if (!empty($source[$key])) {
            $extraSelects[] = "m.`{$source[$key]}` AS {$alias}";
        }
    }

    $sql = "SELECT m.`{$source['id_column']}` AS id, m.`{$source['message_column']}` AS message_text, m.`{$source['created_at_column']}` AS created_at, {$selectSenderRole} AS sender_role, {$senderName} AS sender_name, " . ($source['is_read_column'] ? "m.`{$source['is_read_column']}`" : '0') . " AS is_read" . ($extraSelects ? ', ' . implode(', ', $extraSelects) : '') . " FROM `{$source['table']}` m{$userJoin} WHERE m.`{$source['ticket_id_column']}` = :ticket_id ORDER BY m.`{$source['created_at_column']}` ASC, m.`{$source['id_column']}` ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['ticket_id' => $ticketId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $messages = [];
    foreach ($rows as $row) {
        $messages[] = normalize_ticket_message_chat((array) $row, (array) $ticket, $viewerRole);
    }

    echo json_encode([
        'ticket' => $ticket,
        'messages' => $messages,
        'viewer_role' => $viewerRole,
        'presence' => ticket_presence_chat($pdo, $ticketId, $viewerRole),
        'meta' => [
            'latest_message_id' => (int) (($messages[array_key_last($messages)]['id'] ?? 0)),
            'unread_count' => count(array_filter($messages, static fn (array $message): bool => $message['sender_role'] !== $viewerRole && empty($message['seen_at']))),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load chat.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

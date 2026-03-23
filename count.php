<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');

function table_exists_count(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute(['table_name' => $table]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $exception) {
        return false;
    }
}

function table_columns_count(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        return array_map(static fn (array $row): string => (string) ($row['Field'] ?? ''), $rows);
    } catch (Throwable $exception) {
        return [];
    }
}

function first_existing_column_count(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function resolve_message_source_count(PDO $pdo): ?array
{
    foreach (['ticket_messages', 'messages'] as $table) {
        if (!table_exists_count($pdo, $table)) {
            continue;
        }

        $columns = table_columns_count($pdo, $table);
        $ticketIdColumn = first_existing_column_count($columns, ['ticket_id']);
        $messageColumn = first_existing_column_count($columns, ['message', 'body', 'content', 'text']);
        $createdAtColumn = first_existing_column_count($columns, ['created_at', 'sent_at', 'updated_at']);
        $idColumn = first_existing_column_count($columns, ['id']);
        $isReadColumn = first_existing_column_count($columns, ['is_read']);
        $senderRoleColumn = first_existing_column_count($columns, ['sender_role']);
        $userIdColumn = first_existing_column_count($columns, ['user_id']);
        $sourceColumn = first_existing_column_count($columns, ['source']);
        $seenByAdminColumn = first_existing_column_count($columns, ['seen_by_admin_at']);

        if ($ticketIdColumn && $messageColumn && $createdAtColumn && $idColumn && $isReadColumn) {
            return [
                'table' => $table,
                'ticket_id_column' => $ticketIdColumn,
                'message_column' => $messageColumn,
                'created_at_column' => $createdAtColumn,
                'id_column' => $idColumn,
                'is_read_column' => $isReadColumn,
                'sender_role_column' => $senderRoleColumn,
                'user_id_column' => $userIdColumn,
                'source_column' => $sourceColumn,
                'seen_by_admin_at_column' => $seenByAdminColumn,
            ];
        }
    }

    return null;
}

try {
    $admin = requireLogin();
    if (($admin['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $pdo = db();
    $source = resolve_message_source_count($pdo);

    if ($source === null) {
        echo json_encode([
            'unread_count' => 0,
            'unread_ticket_ids' => [],
            'has_unread' => false,
            'latest' => null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $where = [];

    if (!empty($source['seen_by_admin_at_column'])) {
        $where[] = "m.`{$source['seen_by_admin_at_column']}` IS NULL";
    } else {
        $where[] = "m.`{$source['is_read_column']}` = 0";
    }

    if ($source['sender_role_column']) {
        $where[] = "m.`{$source['sender_role_column']}` = 'user'";
    } elseif ($source['source_column']) {
        $where[] = "COALESCE(m.`{$source['source_column']}`, 'web') <> 'admin'";
    }

    $joinUsers = '';
    if (!$source['sender_role_column'] && $source['user_id_column']) {
        $joinUsers = ' LEFT JOIN users sender ON sender.id = m.`' . $source['user_id_column'] . '`';
        $where[] = "COALESCE(sender.role, 'user') <> 'admin'";
    }

    $whereSql = implode(' AND ', $where);
    $table = $source['table'];

    $countSql = "SELECT COUNT(*) AS unread_count, GROUP_CONCAT(DISTINCT m.`{$source['ticket_id_column']}` ORDER BY m.`{$source['ticket_id_column']}`) AS unread_ticket_ids FROM `{$table}` m{$joinUsers} WHERE {$whereSql}";
    $countStmt = $pdo->query($countSql);
    $countRow = $countStmt ? ($countStmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

    $groupSql = "SELECT m.`{$source['ticket_id_column']}` AS ticket_id, COUNT(*) AS unread_count FROM `{$table}` m{$joinUsers} WHERE {$whereSql} GROUP BY m.`{$source['ticket_id_column']}`";
    $groupStmt = $pdo->query($groupSql);
    $unreadCounts = [];
    foreach (($groupStmt ? $groupStmt->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
        $unreadCounts[(int) ($row['ticket_id'] ?? 0)] = (int) ($row['unread_count'] ?? 0);
    }

    $latestSql = "SELECT m.`{$source['ticket_id_column']}` AS ticket_id, m.`{$source['message_column']}` AS message_text, m.`{$source['created_at_column']}` AS created_at, t.ticket_code, t.subject FROM `{$table}` m LEFT JOIN tickets t ON t.id = m.`{$source['ticket_id_column']}`{$joinUsers} WHERE {$whereSql} ORDER BY m.`{$source['created_at_column']}` DESC, m.`{$source['id_column']}` DESC LIMIT 1";
    $latestStmt = $pdo->query($latestSql);
    $latest = $latestStmt ? ($latestStmt->fetch(PDO::FETCH_ASSOC) ?: null) : null;

    $ticketIds = [];
    if (!empty($countRow['unread_ticket_ids'])) {
        $ticketIds = array_values(array_filter(array_map('intval', explode(',', (string) $countRow['unread_ticket_ids']))));
    }

    echo json_encode([
        'unread_count' => (int) ($countRow['unread_count'] ?? 0),
        'unread_ticket_ids' => $ticketIds,
        'unread_counts' => $unreadCounts,
        'has_unread' => ((int) ($countRow['unread_count'] ?? 0)) > 0,
        'latest' => $latest,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load unread count.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

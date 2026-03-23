<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/lib.php';

function tableExists(PDO $pdo, string $table): bool
{
    try {
        $statement = $pdo->prepare('SHOW TABLES LIKE :table_name');
        $statement->execute(['table_name' => $table]);

        return (bool) $statement->fetchColumn();
    } catch (Throwable $exception) {
        return false;
    }
}

function tableColumns(PDO $pdo, string $table): array
{
    try {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
        $columns = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $exception) {
        return [];
    }

    return array_map(static fn (array $column): string => (string) ($column['Field'] ?? ''), $columns);
}

function firstExistingColumn(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function resolveMessageSource(PDO $pdo): ?array
{
    foreach (['ticket_messages', 'messages'] as $table) {
        if (!tableExists($pdo, $table)) {
            continue;
        }

        $columns = tableColumns($pdo, $table);
        $ticketIdColumn = firstExistingColumn($columns, ['ticket_id']);
        $messageColumn = firstExistingColumn($columns, ['message', 'body', 'content', 'text']);
        $createdAtColumn = firstExistingColumn($columns, ['created_at', 'sent_at', 'updated_at']);
        $idColumn = firstExistingColumn($columns, ['id']);

        if ($ticketIdColumn !== null && $messageColumn !== null && $createdAtColumn !== null && $idColumn !== null) {
            return [
                'table' => $table,
                'ticket_id_column' => $ticketIdColumn,
                'message_column' => $messageColumn,
                'created_at_column' => $createdAtColumn,
                'id_column' => $idColumn,
                'sender_role_column' => firstExistingColumn($columns, ['sender_role']),
                'user_id_column' => firstExistingColumn($columns, ['user_id']),
                'source_column' => firstExistingColumn($columns, ['source']),
            ];
        }
    }

    return null;
}

function buildTicketQuery(string $selectedStatus, ?array $messageSource): array
{
    $messageJoin = '';

    if ($messageSource !== null) {
        $table = $messageSource['table'];
        $ticketIdColumn = $messageSource['ticket_id_column'];
        $messageColumn = $messageSource['message_column'];
        $createdAtColumn = $messageSource['created_at_column'];
        $idColumn = $messageSource['id_column'];

        $messageJoin = ",\n        (
            SELECT tm.`{$messageColumn}`
            FROM `{$table}` tm
            WHERE tm.`{$ticketIdColumn}` = t.id
            ORDER BY tm.`{$createdAtColumn}` DESC, tm.`{$idColumn}` DESC
            LIMIT 1
        ) AS latest_message_text,\n        (
            SELECT tm.`{$createdAtColumn}`
            FROM `{$table}` tm
            WHERE tm.`{$ticketIdColumn}` = t.id
            ORDER BY tm.`{$createdAtColumn}` DESC, tm.`{$idColumn}` DESC
            LIMIT 1
        ) AS latest_message_at,\n        (
            SELECT COUNT(*)
            FROM `{$table}` tm
            WHERE tm.`{$ticketIdColumn}` = t.id
        ) AS message_count";
    } else {
        $messageJoin = ",\n        '' AS latest_message_text,\n        NULL AS latest_message_at,\n        0 AS message_count";
    }

    $sql = "
        SELECT
            t.*,
            COALESCE(u.name, 'Unknown User') AS customer_name,
            COALESCE(u.email, '') AS customer_email{$messageJoin}
        FROM tickets t
        LEFT JOIN users u ON u.id = t.user_id
    ";

    $params = [];

    if ($selectedStatus !== '') {
        $sql .= ' WHERE t.status = :status';
        $params['status'] = $selectedStatus;
    }

    $sql .= ' ORDER BY COALESCE(latest_message_at, t.created_at) DESC, t.id DESC';

    return [$sql, $params];
}

function fetchTickets(PDO $pdo, string $selectedStatus, ?array $messageSource): array
{
    [$sql, $params] = buildTicketQuery($selectedStatus, $messageSource);
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    try {
        $statement = $pdo->prepare('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE :column_name');
        $statement->execute(['column_name' => $column]);

        return (bool) $statement->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        return false;
    }
}

function csrfTokenValue(): string
{
    if (function_exists('csrf_token')) {
        return (string) csrf_token();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function verifyCsrfTokenValue(?string $token): bool
{
    if (function_exists('verify_csrf')) {
        return (bool) verify_csrf();
    }

    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    $token = (string) $token;

    return $sessionToken !== '' && $token !== '' && hash_equals($sessionToken, $token);
}

function orderMessageColumn(PDO $pdo, string $preferred, string $fallback): string
{
    if (columnExists($pdo, 'order_messages', $preferred)) {
        return $preferred;
    }

    if (columnExists($pdo, 'order_messages', $fallback)) {
        return $fallback;
    }

    return $preferred;
}

function fetchOrderMessages(PDO $pdo): array
{
    $result = [
        'rows' => [],
        'error' => '',
    ];

    if (!tableExists($pdo, 'order_messages')) {
        $result['error'] = 'order_messages table not found. Please run SQL setup first.';
        return $result;
    }

    try {
        $classColumn = orderMessageColumn($pdo, 'class_value', 'class');
        $messageColumn = orderMessageColumn($pdo, 'message_text', 'message');
        $replyColumn = orderMessageColumn($pdo, 'admin_reply', 'reply');

        $statement = $pdo->query("SELECT id, student_name, {$classColumn} AS class_value, gender, {$messageColumn} AS message_text, {$replyColumn} AS admin_reply, created_at, replied_at
            FROM order_messages
            ORDER BY created_at DESC, id DESC");

        $result['rows'] = $statement ? ($statement->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (Throwable $exception) {
        $result['error'] = 'Unable to fetch notifications: ' . $exception->getMessage();
    }

    return $result;
}

function countPendingMessages(array $rows): int
{
    $count = 0;

    foreach ($rows as $row) {
        if (trim((string) ($row['admin_reply'] ?? '')) === '') {
            $count++;
        }
    }

    return $count;
}

function buildOrderMessageFeed(array $rows): array
{
    $latestRow = $rows[0] ?? [];
    $items = [];

    foreach ($rows as $row) {
        $items[] = [
            'id' => (int) ($row['id'] ?? 0),
            'student_name' => (string) ($row['student_name'] ?? 'Unknown'),
            'class_value' => (string) ($row['class_value'] ?? '-'),
            'gender' => (string) ($row['gender'] ?? '-'),
            'message_text' => (string) ($row['message_text'] ?? ''),
            'admin_reply' => (string) ($row['admin_reply'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'replied_at' => (string) ($row['replied_at'] ?? ''),
            'has_reply' => trim((string) ($row['admin_reply'] ?? '')) !== '',
        ];
    }

    return [
        'total_count' => count($rows),
        'pending_count' => countPendingMessages($rows),
        'latest_id' => (int) ($latestRow['id'] ?? 0),
        'latest_text' => trim((string) ($latestRow['message_text'] ?? '')),
        'latest_student_name' => (string) ($latestRow['student_name'] ?? ''),
        'latest_created_at' => (string) ($latestRow['created_at'] ?? ''),
        'items' => $items,
    ];
}

function buildTicketFeed(array $tickets): array
{
    $items = [];

    foreach ($tickets as $ticket) {
        $items[] = [
            'id' => (int) ($ticket['id'] ?? 0),
            'ticket_code' => (string) ($ticket['ticket_code'] ?? ''),
            'subject' => (string) ($ticket['subject'] ?? ''),
            'customer_name' => (string) ($ticket['customer_name'] ?? ''),
            'latest_message_text' => trim((string) ($ticket['latest_message_text'] ?? '')),
            'latest_message_at' => (string) ($ticket['latest_message_at'] ?? ''),
            'message_count' => (int) ($ticket['message_count'] ?? 0),
        ];
    }

    return [
        'generated_at' => gmdate('c'),
        'items' => $items,
        'signature' => sha1(json_encode($items)),
    ];
}

function fetchPreviewTicket(PDO $pdo, array $tickets, ?array $messageSource): ?array
{
    $ticket = $tickets[0] ?? null;
    if (!$ticket) {
        return null;
    }

    $preview = [
        'id' => (int) ($ticket['id'] ?? 0),
        'ticket_code' => (string) ($ticket['ticket_code'] ?? ''),
        'subject' => (string) ($ticket['subject'] ?? ''),
        'priority' => (string) ($ticket['priority'] ?? 'Normal'),
        'status' => (string) ($ticket['status'] ?? 'Open'),
        'customer_name' => (string) ($ticket['customer_name'] ?? 'Customer'),
        'latest_message_at' => (string) ($ticket['latest_message_at'] ?? ''),
        'message_count' => (int) ($ticket['message_count'] ?? 0),
        'messages' => [],
    ];

    if ($messageSource === null || $preview['id'] <= 0) {
        if (trim((string) ($ticket['latest_message_text'] ?? '')) !== '') {
            $preview['messages'][] = [
                'sender_role' => 'user',
                'message_text' => trim((string) ($ticket['latest_message_text'] ?? '')),
            ];
        }
        return $preview;
    }

    $selectSenderRole = $messageSource['sender_role_column']
        ? "m.`{$messageSource['sender_role_column']}`"
        : ($messageSource['source_column']
            ? "CASE WHEN COALESCE(m.`{$messageSource['source_column']}`, 'web') = 'admin' THEN 'admin' ELSE 'user' END"
            : ($messageSource['user_id_column']
                ? "CASE WHEN COALESCE(sender.role, 'user') = 'admin' THEN 'admin' ELSE 'user' END"
                : "'user'"));

    $userJoin = $messageSource['user_id_column'] ? ' LEFT JOIN users sender ON sender.id = m.`' . $messageSource['user_id_column'] . '`' : '';
    $sql = "SELECT m.`{$messageSource['message_column']}` AS message_text, {$selectSenderRole} AS sender_role FROM `{$messageSource['table']}` m{$userJoin} WHERE m.`{$messageSource['ticket_id_column']}` = :ticket_id ORDER BY m.`{$messageSource['created_at_column']}` DESC, m.`{$messageSource['id_column']}` DESC LIMIT 3";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['ticket_id' => $preview['id']]);
    $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);

    foreach ($rows as $row) {
        $preview['messages'][] = [
            'sender_role' => trim((string) ($row['sender_role'] ?? 'user')) === 'admin' ? 'admin' : 'user',
            'message_text' => trim((string) ($row['message_text'] ?? '')),
        ];
    }

    if ($preview['messages'] === [] && trim((string) ($ticket['latest_message_text'] ?? '')) !== '') {
        $preview['messages'][] = [
            'sender_role' => 'user',
            'message_text' => trim((string) ($ticket['latest_message_text'] ?? '')),
        ];
    }

    return $preview;
}

function renderLivePreviewCard(?array $preview): string
{
    ob_start();
    if (!$preview) {
        ?>
        <article class="hero-card card premium-card" aria-label="Live preview card">
            <div class="mini-topbar"><span></span><span></span><span></span></div>
            <div class="support-widget">
                <div class="ticket-pill">No live ticket available</div>
                <div class="widget-toolbar">
                    <span class="soft-chip">Waiting for tickets</span>
                    <span class="soft-chip">Preview idle</span>
                </div>
                <div class="chat-bubble customer">When tickets arrive, the newest real conversation will appear here automatically.</div>
            </div>
        </article>
        <?php
        return (string) ob_get_clean();
    }
    ?>
    <article class="hero-card card premium-card" aria-label="Live preview card">
        <div class="mini-topbar"><span></span><span></span><span></span></div>
        <div class="support-widget">
            <div class="ticket-pill">Ticket #<?= e($preview['ticket_code'] ?: ('ID-' . (int) $preview['id'])) ?> · <?= e($preview['priority'] ?: 'Normal') ?></div>
            <div class="widget-toolbar">
                <span class="soft-chip">Customer: <?= e($preview['customer_name'] ?: 'Customer') ?></span>
                <span class="soft-chip">Status: <?= e($preview['status'] ?: 'Open') ?></span>
            </div>
            <?php foreach ((array) ($preview['messages'] ?? []) as $previewMessage): ?>
                <div class="chat-bubble <?= (($previewMessage['sender_role'] ?? 'user') === 'admin') ? 'agent' : 'customer' ?>">
                    <?= e(formatMessagePreview($previewMessage['message_text'] ?? '')) ?>
                </div>
            <?php endforeach; ?>
            <div class="widget-footer">
                <span>Messages: <?= (int) ($preview['message_count'] ?? 0) ?></span>
                <span>Latest: <?= e(formatMetaValue($preview['latest_message_at'] ?? '', 'Pending')) ?></span>
                <span>Status: <?= e($preview['status'] ?: 'Open') ?></span>
            </div>
            <div class="hero-actions preview-actions">
                <a class="primary-btn" href="/qwe1/chat.php?id=<?= (int) ($preview['id'] ?? 0) ?>">Open Live Preview</a>
            </div>
        </div>
    </article>
    <?php

    return (string) ob_get_clean();
}

function formatMessagePreview(?string $message): string
{
    $message = trim((string) $message);

    if ($message === '') {
        return 'No message preview available yet.';
    }

    return mb_strimwidth($message, 0, 140, '...');
}

function formatMetaValue(?string $value, string $fallback): string
{
    $value = trim((string) $value);

    return $value !== '' ? $value : $fallback;
}

$pdo = db();
$admin = requireLogin();

if (($admin['role'] ?? '') !== 'admin') {
    flash('error', 'Access denied.');
    redirect('/qwe1/dashboard.php');
}

$statusOptions = ['Open', 'Pending', 'Resolved'];
$selectedStatus = trim((string) ($_GET['status'] ?? ''));

if ($selectedStatus !== '' && !in_array($selectedStatus, $statusOptions, true)) {
    $selectedStatus = '';
}

$messageSource = resolveMessageSource($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $messageId = (int) ($_POST['message_id'] ?? 0);

    if (isset($_POST['delete_message'])) {
        if (!verifyCsrfTokenValue($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid request token.');
        } elseif ($messageId <= 0) {
            flash('error', 'Invalid message selected.');
        } elseif (!tableExists($pdo, 'order_messages')) {
            flash('error', 'order_messages table not found. Please run SQL setup first.');
        } else {
            try {
                $deleteStatement = $pdo->prepare('DELETE FROM order_messages WHERE id = :id LIMIT 1');
                $deleteStatement->execute(['id' => $messageId]);
                flash($deleteStatement->rowCount() === 1 ? 'success' : 'error', $deleteStatement->rowCount() === 1 ? 'Message deleted successfully.' : 'Message not found.');
            } catch (Throwable $exception) {
                flash('error', 'Unable to delete message right now.');
            }
        }

        redirect('/qwe1/admin.php#message-notifications');
    }

    if (isset($_POST['delete_all_messages'])) {
        if (!verifyCsrfTokenValue($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid request token.');
        } elseif (!tableExists($pdo, 'order_messages')) {
            flash('error', 'order_messages table not found. Please run SQL setup first.');
        } else {
            try {
                $pdo->exec('DELETE FROM order_messages');
                flash('success', 'All messages deleted successfully.');
            } catch (Throwable $exception) {
                flash('error', 'Unable to delete all messages right now.');
            }
        }

        redirect('/qwe1/admin.php#message-notifications');
    }

    if ($action === 'delete' && $ticketId > 0) {
        try {
            $pdo->beginTransaction();

            $ticketStmt = $pdo->prepare('SELECT id, ticket_code FROM tickets WHERE id = :id LIMIT 1');
            $ticketStmt->execute(['id' => $ticketId]);
            $ticket = $ticketStmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                $pdo->rollBack();
                flash('error', 'Ticket not found.');
                redirect('/qwe1/admin.php' . ($selectedStatus !== '' ? '?status=' . urlencode($selectedStatus) : ''));
            }

            foreach (['ticket_messages', 'messages', 'notifications'] as $relatedTable) {
                if (!tableExists($pdo, $relatedTable)) {
                    continue;
                }

                try {
                    $deleteRelated = $pdo->prepare("DELETE FROM `{$relatedTable}` WHERE ticket_id = :ticket_id");
                    $deleteRelated->execute(['ticket_id' => $ticketId]);
                } catch (Throwable $exception) {
                    // Ignore schema differences in related tables so ticket delete still completes.
                }
            }

            $deleteTicket = $pdo->prepare('DELETE FROM tickets WHERE id = :id');
            $deleteTicket->execute(['id' => $ticketId]);
            $pdo->commit();

            flash('success', 'Ticket ' . ($ticket['ticket_code'] ?? ('#' . $ticketId)) . ' deleted successfully.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash('error', 'Unable to delete ticket right now.');
        }

        redirect('/qwe1/admin.php' . ($selectedStatus !== '' ? '?status=' . urlencode($selectedStatus) : ''));
    }
}

$tickets = fetchTickets($pdo, $selectedStatus, $messageSource);
$orderMessageData = fetchOrderMessages($pdo);
$orderMessages = $orderMessageData['rows'];
$orderMessageError = $orderMessageData['error'];
$orderPendingCount = countPendingMessages($orderMessages);
$orderMessageCount = count($orderMessages);
$ticketFeed = buildTicketFeed($tickets);
$orderMessageFeed = buildOrderMessageFeed($orderMessages);
$featuredPreviewTicket = fetchPreviewTicket($pdo, $tickets, $messageSource);
$previewHtml = renderLivePreviewCard($featuredPreviewTicket);
$liveFeed = $ticketFeed;
$liveFeed['order_messages'] = $orderMessageFeed;
$liveFeed['preview_ticket'] = $featuredPreviewTicket;
$liveFeed['preview_html'] = $previewHtml;
$liveFeed['signature'] = sha1(json_encode([$ticketFeed['items'], $orderMessageFeed, $featuredPreviewTicket]));

if (($_GET['feed'] ?? '') === 'ticket-activity') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($liveFeed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$flash = getFlash();
renderHead('Admin Tickets', 'Admin control room for ticket operations, messages, and live notifications.');
?>
<body>
<style>
:root {
    --primary: #6d8cff;
    --secondary: #9333ea;
    --danger: #ef4444;
    --danger-dark: #dc2626;
    --success: #22c55e;
    --warning: #f59e0b;
    --info: #3b82f6;
    --surface: rgba(10, 22, 40, 0.82);
    --surface-soft: rgba(255, 255, 255, 0.05);
    --surface-strong: rgba(9, 17, 31, 0.95);
    --border: rgba(255, 255, 255, 0.08);
    --text: #e2e8f0;
    --muted: #9fb1cc;
    --icon-bg: rgba(109, 140, 255, 0.18);
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: var(--text);
    background:
        radial-gradient(circle at top left, rgba(109, 140, 255, 0.16), transparent 30%),
        radial-gradient(circle at top right, rgba(147, 51, 234, 0.13), transparent 30%),
        linear-gradient(180deg, #09111f, #050c17);
}

.page-shell {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
}

.ambient {
    position: absolute;
    width: 400px;
    height: 400px;
    filter: blur(120px);
    opacity: 0.26;
    z-index: 0;
}

.ambient-a {
    background: var(--primary);
    top: -100px;
    left: -100px;
}

.ambient-b {
    background: var(--secondary);
    bottom: -100px;
    right: -100px;
}

.glass,
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    backdrop-filter: blur(18px);
    box-shadow: 0 14px 40px rgba(0, 0, 0, 0.22);
}

.topbar {
    position: sticky;
    top: 18px;
    z-index: 10;
    width: min(1220px, calc(100% - 32px));
    margin: 20px auto;
    padding: 16px 20px;
    border-radius: 24px;
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: center;
}

.brand {
    display: flex;
    gap: 12px;
    align-items: center;
}

.brand.small {
    align-items: flex-start;
}

.brand-mark {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    flex-shrink: 0;
}

.brand p,
.panel-head p,
.ticket-meta,
small,
.empty-copy,
.muted,
.message-meta,
.message-preview {
    color: var(--muted);
}

.menu,
.stack-links,
.hero-actions,
.overview-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.menu a,
.stack-links a,
.secondary-btn,
.message-notification-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid transparent;
    color: var(--muted);
    text-decoration: none;
    transition: 0.2s ease;
}

.menu a:hover,
.stack-links a:hover,
.secondary-btn:hover,
.message-notification-btn:hover,
.stack-links a.active {
    color: #fff;
    border-color: rgba(109, 140, 255, 0.45);
    background: rgba(109, 140, 255, 0.16);
}

.dashboard-shell {
    position: relative;
    z-index: 1;
    width: min(1220px, calc(100% - 32px));
    margin: 0 auto;
    padding: 10px 0 40px;
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    gap: 22px;
}

.sidebar,
.panel-head,
.ticket-row,
.overview-card,
.empty {
    border-radius: 22px;
}

.sidebar {
    padding: 20px;
    height: fit-content;
}

.stack-links {
    margin-top: 18px;
    flex-direction: column;
}

.stack-links a {
    width: 100%;
    justify-content: flex-start;
    background: rgba(255, 255, 255, 0.04);
}

.main-panel {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.panel-head {
    padding: 22px;
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: flex-start;
}

.eyebrow {
    display: inline-block;
    margin-bottom: 10px;
    color: var(--primary);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.overview-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.overview-card {
    padding: 18px;
}

.overview-card strong {
    display: block;
    margin-top: 8px;
    font-size: 26px;
}

.ticket-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.ticket-row {
    padding: 20px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 18px;
    align-items: center;
}

.ticket-row:hover {
    transform: translateY(-2px);
}

.ticket-content {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.ticket-title {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.ticket-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.meta-pill svg,
.message-icon svg,
.live-indicator svg,
.empty svg,
.toast svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.message-box {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    padding: 14px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(109, 140, 255, 0.1), rgba(147, 51, 234, 0.08));
    border: 1px solid rgba(109, 140, 255, 0.18);
}

.message-icon {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: var(--icon-bg);
    color: #dbe6ff;
}

.message-body {
    min-width: 0;
}

.message-heading {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 6px;
}

.message-heading strong {
    color: #fff;
}

.message-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    min-height: 28px;
    padding: 0 9px;
    border-radius: 999px;
    background: rgba(34, 197, 94, 0.16);
    border: 1px solid rgba(34, 197, 94, 0.28);
    color: #dcfce7;
    font-size: 12px;
    font-weight: 700;
}

.message-preview {
    margin: 0;
    line-height: 1.55;
    word-break: break-word;
}


.hero-preview-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(260px, 420px);
    gap: 18px;
    align-items: start;
    margin-top: 18px;
}

.hero-card {
    padding: 18px;
    border-radius: 24px;
}

.premium-card {
    background: linear-gradient(135deg, rgba(109, 140, 255, 0.16), rgba(147, 51, 234, 0.14));
    border: 1px solid rgba(109, 140, 255, 0.24);
}

.mini-topbar {
    display: flex;
    gap: 8px;
    margin-bottom: 14px;
}

.mini-topbar span {
    width: 12px;
    height: 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.22);
}

.support-widget {
    display: grid;
    gap: 12px;
}

.ticket-pill,
.soft-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 8px 12px;
    width: fit-content;
}

.ticket-pill {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    font-weight: 700;
}

.widget-toolbar,
.widget-footer,
.preview-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.soft-chip,
.widget-footer span {
    background: rgba(15, 23, 42, 0.32);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #dbe6ff;
    font-size: 12px;
}

.chat-bubble {
    max-width: 100%;
    padding: 12px 14px;
    border-radius: 18px;
    line-height: 1.55;
    word-break: break-word;
}

.chat-bubble.customer {
    background: rgba(255, 255, 255, 0.08);
    justify-self: start;
}

.chat-bubble.agent {
    background: rgba(34, 197, 94, 0.16);
    justify-self: end;
}

.message-meta {
    font-size: 12px;
    text-align: right;
}

.ticket-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: center;
}

.badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
}

.badge.open { background: var(--success); }
.badge.pending { background: var(--warning); }
.badge.resolved { background: var(--info); }

.live-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 999px;
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.25);
    color: #dcfce7;
    font-size: 12px;
    font-weight: 700;
}

.message-notification-btn {
    position: relative;
    gap: 8px;
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.12);
    color: #fff;
}

.message-notification-btn svg {
    width: 16px;
    height: 16px;
}

.notification-badge {
    min-width: 22px;
    min-height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    border-radius: 999px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    font-size: 11px;
    font-weight: 700;
}

.notification-badge.hidden {
    display: none;
}

.notification-badge.bump {
    animation: badgeBounce 0.4s ease;
}

.notification-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: #ff3b30;
    box-shadow: 0 0 0 rgba(255, 59, 48, 0.4);
    display: none;
}

.notification-dot.visible {
    display: inline-flex;
    animation: pulseDot 1.4s infinite;
}

.new-message-label {
    display: none;
    padding: 4px 9px;
    border-radius: 999px;
    background: rgba(37, 211, 102, 0.16);
    color: #dcfce7;
    font-size: 11px;
    font-weight: 700;
}

.new-message-label.visible {
    display: inline-flex;
}

.ticket-row.unread {
    border-color: rgba(37, 211, 102, 0.3);
    box-shadow: 0 10px 30px rgba(37, 211, 102, 0.08);
}

.ticket-row.unread .ticket-title strong {
    color: #fff;
}

@keyframes badgeBounce {
    0% { transform: scale(1); }
    45% { transform: scale(1.22); }
    100% { transform: scale(1); }
}

@keyframes pulseDot {
    0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 59, 48, 0.5); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 59, 48, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 59, 48, 0); }
}

.view-btn,
.delete-btn {
    min-height: 40px;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid transparent;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s ease;
}

.view-btn {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: #fff;
    text-decoration: none;
}

.delete-btn {
    background: rgba(239, 68, 68, 0.12);
    border-color: rgba(239, 68, 68, 0.35);
    color: #fff;
}

.delete-btn:hover {
    background: var(--danger);
    border-color: var(--danger-dark);
}

.delete-form {
    margin: 0;
}

.flash {
    width: min(1220px, calc(100% - 32px));
    margin: 16px auto 0;
    padding: 14px 16px;
    border-radius: 14px;
    position: relative;
    z-index: 1;
}

.flash.success { background: rgba(34, 197, 94, 0.18); border: 1px solid rgba(34, 197, 94, 0.35); }
.flash.error { background: rgba(239, 68, 68, 0.18); border: 1px solid rgba(239, 68, 68, 0.35); }

.empty {
    padding: 28px;
    display: grid;
    place-items: center;
    text-align: center;
    gap: 14px;
}

.empty-icon {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.06);
    color: #dbe6ff;
}

.notification-toast {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 50;
    display: grid;
    gap: 8px;
}

.toast {
    display: none;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    max-width: 360px;
    padding: 14px 16px;
    border-radius: 18px;
    color: #fff;
    background: var(--surface-strong);
    border: 1px solid rgba(109, 140, 255, 0.25);
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
}

.toast.show {
    display: grid;
}

.toast-title {
    font-weight: 700;
    margin-bottom: 4px;
}

.toast-copy {
    color: var(--muted);
    font-size: 14px;
    line-height: 1.45;
}

@media (max-width: 1080px) {
    .overview-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 960px) {
    .dashboard-shell {
        grid-template-columns: 1fr;
    }

    .topbar,
    .panel-head,
    .ticket-row {
        grid-template-columns: 1fr;
    }

    .ticket-actions {
        justify-content: flex-start;
    }

    .stack-links {
        flex-direction: row;
    }

    .stack-links a {
        width: auto;
    }
}

@media (max-width: 720px) {
    .message-box {
        grid-template-columns: auto minmax(0, 1fr);
    }

    .message-meta {
        grid-column: 1 / -1;
        text-align: left;
    }
}

@media (max-width: 640px) {
    .topbar,
    .panel-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .menu,
    .hero-actions,
    .ticket-actions,
    .stack-links {
        width: 100%;
    }

    .menu a,
    .stack-links a,
    .secondary-btn,
    .view-btn,
    .delete-btn {
        width: 100%;
    }

    .notification-toast {
        right: 12px;
        left: 12px;
        bottom: 12px;
    }

    .toast {
        max-width: none;
    }
}
</style>

<div class="page-shell app-page">
    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-b"></div>

    <?php if (!empty($flash)): ?>
        <div class="flash <?= e($flash['type'] ?? '') ?>"><?= e($flash['message'] ?? '') ?></div>
    <?php endif; ?>

    <header class="topbar glass" aria-label="Admin header">
        <div class="brand">
            <div class="brand-mark">AD</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Admin control room for ticket operations and customer communication.</p>
            </div>
        </div>

        <nav class="menu" aria-label="Admin navigation">
            <a href="/qwe1/">Home</a>
            <a href="/qwe1/admin.php">All Tickets</a>
            <a class="message-notification-btn" href="/qwe1/admin.php#message-notifications">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10h10M7 14h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6 19v-3.2A4.8 4.8 0 0 1 4 12c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7c-1.4 0-2.717-.315-3.86-.871L6 19Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                Messages
                <span class="new-message-label" data-new-message-label>New Message</span>
                <span class="notification-dot" data-message-dot></span>
                <span class="notification-badge <?= $orderPendingCount > 0 ? '' : 'hidden' ?>" data-message-badge><?= (int) $orderPendingCount ?></span>
            </a>
            <a href="/qwe1/logout.php">Logout</a>
        </nav>
    </header>

    <main class="dashboard-shell admin-mode">
        <aside class="sidebar card" aria-label="Admin sidebar">
            <div class="brand small">
                <div class="brand-mark">AD</div>
                <div>
                    <strong><?= e($admin['name'] ?? 'Admin') ?></strong>
                    <p><?= e($admin['email'] ?? '') ?></p>
                </div>
            </div>

            <nav class="stack-links" aria-label="Admin filters">
                <a class="<?= $selectedStatus === '' ? 'active' : '' ?>" href="/qwe1/admin.php">All Tickets</a>
                <a class="<?= $selectedStatus === 'Open' ? 'active' : '' ?>" href="/qwe1/admin.php?status=Open">Open</a>
                <a class="<?= $selectedStatus === 'Pending' ? 'active' : '' ?>" href="/qwe1/admin.php?status=Pending">Pending</a>
                <a class="<?= $selectedStatus === 'Resolved' ? 'active' : '' ?>" href="/qwe1/admin.php?status=Resolved">Resolved</a>
                <a href="/qwe1/logout.php">Logout</a>
            </nav>
        </aside>

        <section class="main-panel">
            <header class="panel-head card">
                <div>
                    <span class="eyebrow">Admin secure panel</span>
                    <h1>Support ticket control room</h1>
                    <p class="muted">
                        Professional ticket list with message previews, live notification sound, and quick actions for reviewing each conversation.
                    </p>
                </div>

                <div class="hero-actions">
                    <span class="live-indicator">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v4m0 10v4m9-9h-4M7 12H3m15.364-6.364-2.828 2.828M8.464 15.536l-2.828 2.828m0-12.728 2.828 2.828m9.9 7.072 2.828 2.828" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        Live message alerts without refresh
                    </span>
                    <a class="message-notification-btn" href="/qwe1/admin.php#message-notifications">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10h10M7 14h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6 19v-3.2A4.8 4.8 0 0 1 4 12c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7c-1.4 0-2.717-.315-3.86-.871L6 19Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        Messages
                        <span class="new-message-label" data-new-message-label>New Message</span>
                        <span class="notification-dot" data-message-dot></span>
                        <span class="notification-badge <?= $orderPendingCount > 0 ? '' : 'hidden' ?>" data-message-badge><?= (int) $orderPendingCount ?></span>
                    </a>
                    <a class="secondary-btn" href="/qwe1/">Landing</a>
                    <a class="secondary-btn" href="/qwe1/new-ticket.php">Create Ticket</a>
                </div>
            </header>

            <section class="overview-grid" aria-label="Ticket overview">
                <article class="overview-card card">
                    <span class="eyebrow">Visible tickets</span>
                    <strong><?= count($tickets) ?></strong>
                    <p class="muted">Tickets shown in the current filter view.</p>
                </article>
                <article class="overview-card card">
                    <span class="eyebrow">Message source</span>
                    <strong><?= e($messageSource['table'] ?? 'Unavailable') ?></strong>
                    <p class="muted">Live previews use this message table when it exists.</p>
                </article>
                <article class="overview-card card">
                    <span class="eyebrow">Current filter</span>
                    <strong><?= e($selectedStatus !== '' ? $selectedStatus : 'All') ?></strong>
                    <p class="muted">Change filters from the admin sidebar.</p>
                </article>
            </section>

            <section class="card" id="message-notifications" aria-label="Message notifications" data-order-base-url="/public_html/myorders.php" data-csrf-token="<?= e(csrfTokenValue()) ?>" style="padding: 22px;">
                <div class="panel-head" style="padding: 0; background: transparent; border: 0; box-shadow: none;">
                    <div>
                        <span class="eyebrow">Message notifications</span>
                        <h2 style="margin: 0 0 8px;">Order messages</h2>
                        <p class="muted">Click any message below to open the related order page instantly.</p>
                    </div>
                    <div class="hero-actions">
                        <span class="meta-pill">Pending: <strong id="order-pending-count"><?= (int) $orderPendingCount ?></strong></span>
                        <span class="meta-pill">Total: <strong id="order-total-count"><?= (int) $orderMessageCount ?></strong></span>
                        <?php if ($orderMessageCount > 0): ?>
                            <form method="post" onsubmit="return confirm('Are you sure to delete all messages?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfTokenValue()) ?>">
                                <button type="submit" name="delete_all_messages" value="1" class="delete-btn">Delete All Messages</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="hero-preview-grid">
                    <div>
                        <div class="message-box" style="cursor: default;">
                            <div class="message-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h10M4 17h13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>
                            </div>
                            <div class="message-body">
                                <div class="message-heading"><strong>Live message preview</strong></div>
                                <p class="message-preview">This block now uses the newest real ticket conversation instead of demo content.</p>
                            </div>
                            <div class="message-meta">Real-time</div>
                        </div>
                    </div>
                    <div id="live-preview-panel">
                        <?= $previewHtml ?>
                    </div>
                </div>

                <?php if ($orderMessageError !== ''): ?>
                    <div class="flash error" style="width: 100%; margin-top: 18px;"><?= e($orderMessageError) ?></div>
                <?php endif; ?>

                <div class="ticket-list" id="order-message-list" style="margin-top: 18px;">
                    <?php if ($orderMessageError === '' && $orderMessages): ?>
                        <?php foreach ($orderMessages as $messageRow): ?>
                            <?php $hasReply = trim((string) ($messageRow['admin_reply'] ?? '')) !== ''; ?>
                            <article class="ticket-row card" style="padding: 18px; border-left: 4px solid <?= $hasReply ? 'rgba(34, 197, 94, 0.7)' : 'rgba(245, 158, 11, 0.9)' ?>;">
                                <div class="ticket-content">
                                    <div class="ticket-title">
                                        <strong><?= e((string) ($messageRow['student_name'] ?? 'Unknown')) ?></strong>
                                        <span class="badge <?= $hasReply ? 'resolved' : 'pending' ?>"><?= $hasReply ? 'Replied' : 'Pending Reply' ?></span>
                                    </div>
                                    <div class="ticket-meta">
                                        <span class="meta-pill">Class <?= e((string) ($messageRow['class_value'] ?? '-')) ?></span>
                                        <span class="meta-pill"><?= e((string) ($messageRow['gender'] ?? '-')) ?></span>
                                        <span class="meta-pill">Sent: <?= e((string) ($messageRow['created_at'] ?? '-')) ?></span>
                                        <span class="meta-pill">Message ID: <?= (int) ($messageRow['id'] ?? 0) ?></span>
                                    </div>
                                    <div class="message-box" data-open-url="/public_html/myorders.php?student_name=<?= urlencode((string) ($messageRow['student_name'] ?? '')) ?>&class=<?= urlencode((string) ($messageRow['class_value'] ?? '')) ?>&gender=<?= urlencode((string) ($messageRow['gender'] ?? '')) ?>#message-section">
                                        <div class="message-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none"><path d="M7 10h10M7 14h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6 19v-3.2A4.8 4.8 0 0 1 4 12c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7c-1.4 0-2.717-.315-3.86-.871L6 19Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                        </div>
                                        <div class="message-body">
                                            <div class="message-heading">
                                                <strong>Message</strong>
                                            </div>
                                            <p class="message-preview"><?= nl2br(e((string) ($messageRow['message_text'] ?? ''))) ?></p>
                                        </div>
                                        <div class="message-meta">
                                            <?= $hasReply ? 'Reply updated' : 'Awaiting admin reply' ?>
                                        </div>
                                    </div>
                                    <?php if ($hasReply): ?>
                                        <div class="message-box" style="background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18);">
                                            <div class="message-icon" aria-hidden="true" style="background: rgba(34, 197, 94, 0.16);">
                                                <svg viewBox="0 0 24 24" fill="none"><path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </div>
                                            <div class="message-body">
                                                <div class="message-heading">
                                                    <strong>Admin reply</strong>
                                                </div>
                                                <p class="message-preview"><?= nl2br(e((string) ($messageRow['admin_reply'] ?? ''))) ?></p>
                                            </div>
                                            <div class="message-meta">Reply time: <?= e((string) ($messageRow['replied_at'] ?? '-')) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ticket-actions">
                                    <a class="view-btn" href="/public_html/myorders.php?student_name=<?= urlencode((string) ($messageRow['student_name'] ?? '')) ?>&class=<?= urlencode((string) ($messageRow['class_value'] ?? '')) ?>&gender=<?= urlencode((string) ($messageRow['gender'] ?? '')) ?>#message-section">Open in Order Page</a>
                                    <form method="post" class="delete-form" onsubmit="return confirm('Delete this message?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfTokenValue()) ?>">
                                        <input type="hidden" name="message_id" value="<?= (int) ($messageRow['id'] ?? 0) ?>">
                                        <button type="submit" name="delete_message" value="1" class="delete-btn">Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="empty" id="order-message-empty" style="margin-top: 18px; display: <?= ($orderMessageError === '' && $orderMessages) ? 'none' : 'grid' ?>;">
                    <div class="empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M7 10h10M7 14h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6 19v-3.2A4.8 4.8 0 0 1 4 12c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7c-1.4 0-2.717-.315-3.86-.871L6 19Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    </div>
                    <strong>No message notifications yet.</strong>
                </div>
            </section>

            <section class="ticket-list" aria-label="Admin tickets" data-initial-unread-count="0" data-websocket-url="" data-ticket-feed='<?= e(json_encode($liveFeed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>' data-feed-url="/qwe1/admin.php?feed=ticket-activity<?= $selectedStatus !== '' ? '&amp;status=' . urlencode($selectedStatus) : '' ?>">
                <?php foreach ($tickets as $ticket): ?>
                    <article class="ticket-row card" data-ticket-id="<?= (int) ($ticket['id'] ?? 0) ?>" data-ticket-signature="<?= e(($ticket['latest_message_at'] ?? '') . '|' . ($ticket['message_count'] ?? 0)) ?>">
                        <div class="ticket-content">
                            <div class="ticket-title">
                                <strong><?= e($ticket['subject'] ?? 'Untitled Ticket') ?></strong>
                                <span class="badge <?= strtolower((string) ($ticket['status'] ?? 'open')) ?>"><?= e($ticket['status'] ?? 'Open') ?></span>
                            </div>

                            <div class="ticket-meta">
                                <span class="meta-pill">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a7 7 0 0 0-14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <?= e(formatMetaValue($ticket['customer_name'] ?? null, 'Unknown User')) ?>
                                </span>
                                <span class="meta-pill">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4 6 8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="1.8"/></svg>
                                    <?= e(formatMetaValue($ticket['customer_email'] ?? null, 'No email')) ?>
                                </span>
                                <span class="meta-pill">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 7h10M7 12h10M7 17h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="4" y="4" width="16" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/></svg>
                                    <?= e($ticket['ticket_code'] ?? '') ?>
                                </span>
                                <span class="meta-pill">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/></svg>
                                    Priority: <?= e($ticket['priority'] ?? 'Normal') ?>
                                </span>
                                <?php if (!empty($ticket['order_reference'])): ?>
                                    <span class="meta-pill">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 7h14M7 12h10M9 17h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><rect x="4" y="4" width="16" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/></svg>
                                        Order: <?= e($ticket['order_reference']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="message-box" data-open-url="/qwe1/chat.php?id=<?= (int) ($ticket['id'] ?? 0) ?>">
                                <div class="message-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M7 10h10M7 14h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6 19v-3.2A4.8 4.8 0 0 1 4 12c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7c-1.4 0-2.717-.315-3.86-.871L6 19Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                </div>
                                <div class="message-body">
                                    <div class="message-heading">
                                        <strong>Latest message</strong>
                                        <span class="message-count" data-ticket-message-count="<?= (int) ($ticket['id'] ?? 0) ?>"><?= (int) ($ticket['message_count'] ?? 0) ?></span>
                                    </div>
                                    <p class="message-preview"><?= e(formatMessagePreview($ticket['latest_message_text'] ?? null)) ?></p>
                                </div>
                                <div class="message-meta">
                                    <?= !empty($ticket['latest_message_at']) ? e((string) $ticket['latest_message_at']) : 'Waiting for first reply' ?>
                                </div>
                            </div>
                        </div>

                        <div class="ticket-actions">
                            <a class="view-btn" href="/qwe1/chat.php?id=<?= (int) ($ticket['id'] ?? 0) ?>">Open Live Chat</a>
                            <form class="delete-form" method="post" onsubmit="return confirm('Are you sure you want to delete ticket <?= e($ticket['ticket_code'] ?? '') ?>?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="ticket_id" value="<?= (int) ($ticket['id'] ?? 0) ?>">
                                <button class="delete-btn" type="submit">Delete Ticket</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if (!$tickets): ?>
                    <div class="card empty">
                        <div class="empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M7 10h10M7 14h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6 19v-3.2A4.8 4.8 0 0 1 4 12c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7c-1.4 0-2.717-.315-3.86-.871L6 19Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                        </div>
                        <strong>No tickets found for this filter.</strong>
                        <p class="empty-copy">When new customer messages arrive, they will appear here with message preview, icon, and live alert support.</p>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </main>

    <div class="notification-toast" aria-live="polite" aria-atomic="true">
        <div class="toast" id="ticket-toast">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4a6 6 0 0 0-6 6v2.764c0 .424-.168.831-.468 1.13L4 15.428V17h16v-1.572l-1.532-1.534A1.598 1.598 0 0 1 18 12.764V10a6 6 0 0 0-6-6Zm-2.5 15a2.5 2.5 0 0 0 5 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <div>
                <div class="toast-title" id="ticket-toast-title">New ticket activity</div>
                <div class="toast-copy" id="ticket-toast-copy">A new message has arrived.</div>
            </div>
        </div>
    </div>

    <?php renderFooter(); ?>
</div>
<div style="position:fixed;left:24px;bottom:24px;z-index:18;display:flex;gap:10px;flex-wrap:wrap;align-items:center;padding:12px 14px;border-radius:18px;background:rgba(15,23,42,.84);border:1px solid rgba(148,163,184,.16);backdrop-filter:blur(16px);">
    <button type="button" id="dashboard-mute-toggle" class="message-notification-btn">🔊 Sound on</button>
    <label style="display:flex;align-items:center;gap:10px;font-size:12px;color:#cbd5f5;" for="dashboard-volume-control">🔥 Volume <input id="dashboard-volume-control" type="range" min="0" max="1" step="0.05" value="0.75"></label>
</div>
<audio id="admin-notification-sound" preload="auto" src="/qwe1/assets/notification.mp3"></audio>
<script>
(() => {
    const ticketList = document.querySelector('.ticket-list[data-ticket-feed]');
    const toast = document.getElementById('ticket-toast');
    const toastTitle = document.getElementById('ticket-toast-title');
    const toastCopy = document.getElementById('ticket-toast-copy');
    const badgeNodes = Array.from(document.querySelectorAll('[data-message-badge]'));
    const messageDots = Array.from(document.querySelectorAll('[data-message-dot]'));
    const newMessageLabels = Array.from(document.querySelectorAll('[data-new-message-label]'));
    const ticketRows = Array.from(document.querySelectorAll('.ticket-row[data-ticket-id]'));
    const messageCountNodes = Array.from(document.querySelectorAll('[data-ticket-message-count]'));
    const orderSection = document.getElementById('message-notifications');
    const orderMessageList = document.getElementById('order-message-list');
    const orderMessageEmpty = document.getElementById('order-message-empty');
    const orderPendingCount = document.getElementById('order-pending-count');
    const orderTotalCount = document.getElementById('order-total-count');
    const previewPanel = document.getElementById('live-preview-panel');
    const soundNode = document.getElementById('admin-notification-sound');
    const muteToggle = document.getElementById('dashboard-mute-toggle');
    const volumeControl = document.getElementById('dashboard-volume-control');

    if (!ticketList || !toast || !toastTitle || !toastCopy || !orderSection) {
        return;
    }

    const feedUrl = ticketList.dataset.feedUrl;
    const websocketUrl = ticketList.dataset.websocketUrl || '';
    let currentFeed;
    let lastUnreadCount = Number(ticketList.dataset.initialUnreadCount || 0);
    let latestUnreadPayload = {unread_counts: {}, unread_ticket_ids: []};

    try {
        currentFeed = JSON.parse(ticketList.dataset.ticketFeed || '{}');
    } catch (error) {
        currentFeed = {items: [], signature: '', order_messages: {}};
    }

    const audioContext = window.AudioContext ? new AudioContext() : (window.webkitAudioContext ? new webkitAudioContext() : null);
    const soundSettings = (() => {
        try {
            return {
                volume: Number(localStorage.getItem('admin-dashboard-volume') || '0.75') || 0.75,
                muted: localStorage.getItem('admin-dashboard-muted') === '1'
            };
        } catch (error) {
            return {volume: 0.75, muted: false};
        }
    })();

    const syncDashboardSoundUi = () => {
        if (volumeControl) {
            volumeControl.value = String(soundSettings.volume);
        }
        if (muteToggle) {
            muteToggle.textContent = soundSettings.muted ? '🔇 Muted' : '🔊 Sound on';
        }
        if (soundNode) {
            soundNode.volume = soundSettings.volume;
        }
    };

    const persistDashboardSoundUi = () => {
        try {
            localStorage.setItem('admin-dashboard-volume', String(soundSettings.volume));
            localStorage.setItem('admin-dashboard-muted', soundSettings.muted ? '1' : '0');
        } catch (error) {}
    };

    const playUnreadSound = async () => {
        if (soundSettings.muted) {
            return;
        }

        if (soundNode) {
            try {
                soundNode.currentTime = 0;
                await soundNode.play();
                return;
            } catch (error) {
                // Fallback to oscillator tone when mp3 playback is blocked.
            }
        }

        await playNotificationTone();
    };

    const setUnreadUi = (unreadCount, unreadTicketIds = []) => {
        const hasUnread = unreadCount > 0;
        badgeNodes.forEach((node) => {
            node.textContent = String(unreadCount);
            node.classList.toggle('hidden', !hasUnread);
        });
        messageDots.forEach((node) => node.classList.toggle('visible', hasUnread));
        newMessageLabels.forEach((node) => node.classList.toggle('visible', hasUnread));

        const unreadSet = new Set((unreadTicketIds || []).map((id) => Number(id)));
        ticketRows.forEach((row) => {
            const ticketId = Number(row.dataset.ticketId || 0);
            const isUnread = unreadSet.has(ticketId);
            row.classList.toggle('unread', isUnread);

            let label = row.querySelector('[data-chat-label]');
            if (!label) {
                label = document.createElement('span');
                label.className = 'new-message-label';
                label.setAttribute('data-chat-label', 'true');
                label.textContent = 'New Message';
                const title = row.querySelector('.ticket-title');
                if (title) {
                    title.appendChild(label);
                }
            }

            label.classList.toggle('visible', isUnread);
        });
    };

    const updateTicketMessageCounts = (unreadCounts = {}) => {
        messageCountNodes.forEach((node) => {
            const ticketId = Number(node.dataset.ticketMessageCount || 0);
            const unreadCount = Number(unreadCounts[ticketId] || 0);
            if (unreadCount > 0) {
                node.textContent = String(unreadCount);
                node.classList.add('visible');
            }
        });
    };

    const pollUnreadCount = async () => {
        try {
            const response = await fetch('/qwe1/count.php', {
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const unreadCount = Number(payload.unread_count || 0);
            latestUnreadPayload = payload;
            setUnreadUi(unreadCount, payload.unread_ticket_ids || []);
            updateTicketMessageCounts(payload.unread_counts || {});
            renderOrderMessages(currentFeed);

            if (unreadCount > lastUnreadCount) {
                badgeNodes.forEach((node) => {
                    node.classList.remove('bump');
                    void node.offsetWidth;
                    node.classList.add('bump');
                });

                const latest = payload.latest || {};
                showToast({
                    ticket_code: latest.ticket_code || 'Unread Message',
                    latest_message_text: latest.message_text || 'A new customer message has arrived.',
                    customer_name: latest.subject || 'Support ticket'
                });
                showBrowserNotification(`Unread messages: ${unreadCount}`, latest.message_text || 'A new customer message has arrived.');
                playUnreadSound();
            }

            lastUnreadCount = unreadCount;
        } catch (error) {
            // Ignore unread polling failures.
        }
    };

    const playNotificationTone = async () => {
        if (!audioContext || soundSettings.muted) {
            return;
        }

        if (audioContext.state === 'suspended') {
            try {
                await audioContext.resume();
            } catch (error) {
                return;
            }
        }

        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(660, audioContext.currentTime + 0.18);
        gainNode.gain.setValueAtTime(0.0001, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(Math.max(0.02, soundSettings.volume * 0.08), audioContext.currentTime + 0.02);
        gainNode.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.45);
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.45);
    };

    const showToast = (item) => {
        toastTitle.textContent = `New message • ${item.ticket_code || 'Ticket'}`;
        toastCopy.textContent = item.latest_message_text || `${item.customer_name || 'Customer'} sent a new message.`;
        toast.classList.add('show');
        window.clearTimeout(showToast.timeoutId);
        showToast.timeoutId = window.setTimeout(() => {
            toast.classList.remove('show');
        }, 4200);
    };

    const getItemMap = (feed) => {
        const items = Array.isArray(feed.items) ? feed.items : [];
        return new Map(items.map((item) => [String(item.id), item]));
    };

    const updateBadges = (feed) => {
        const pendingMessages = Number(feed?.order_messages?.pending_count || 0);
        badgeNodes.forEach((node) => {
            node.textContent = String(pendingMessages);
        });
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const connectRealtime = () => {
        if (!websocketUrl || !('WebSocket' in window)) {
            return false;
        }

        try {
            const socket = new WebSocket(websocketUrl);
            socket.addEventListener('message', () => {
                checkForUpdates();
                pollUnreadCount();
            });
            socket.addEventListener('error', () => {
                socket.close();
            });
            return true;
        } catch (error) {
            return false;
        }
    };

    const renderOrderMessages = (feed) => {
        const orderFeed = feed?.order_messages || {};
        const items = Array.isArray(orderFeed.items) ? orderFeed.items : [];
        const unreadCounts = latestUnreadPayload.unread_counts || {};
        const ticketItems = Array.isArray(feed?.items) ? feed.items.filter((item) => (item.latest_message_text || '').trim() !== '') : [];

        if (orderPendingCount) {
            const visiblePending = Number(orderFeed.pending_count || 0) || Number(latestUnreadPayload.unread_count || 0);
            orderPendingCount.textContent = String(visiblePending);
        }

        if (orderTotalCount) {
            const visibleTotal = Number(orderFeed.total_count || 0) || ticketItems.length;
            orderTotalCount.textContent = String(visibleTotal);
        }

        if (orderMessageEmpty) {
            orderMessageEmpty.style.display = (items.length || ticketItems.length) ? 'none' : 'grid';
        }

        if (!orderMessageList) {
            return;
        }

        const csrfToken = orderSection.dataset.csrfToken || '';
        const orderBaseUrl = orderSection.dataset.orderBaseUrl || '/public_html/myorders.php';

        if (items.length) {
            orderMessageList.innerHTML = items.map((item) => {
                const hasReply = Boolean(item.has_reply);
                const borderColor = hasReply ? 'rgba(34, 197, 94, 0.7)' : 'rgba(245, 158, 11, 0.9)';
                const orderUrl = `${orderBaseUrl}?student_name=${encodeURIComponent(item.student_name || '')}&class=${encodeURIComponent(item.class_value || '')}&gender=${encodeURIComponent(item.gender || '')}#message-section`;

                return `
                <article class="ticket-row card" style="padding: 18px; border-left: 4px solid ${borderColor};">
                    <div class="ticket-content">
                        <div class="ticket-title">
                            <strong>${escapeHtml(item.student_name || 'Unknown')}</strong>
                            <span class="badge ${hasReply ? 'resolved' : 'pending'}">${hasReply ? 'Replied' : 'Pending Reply'}</span>
                        </div>
                        <div class="ticket-meta">
                            <span class="meta-pill">Class ${escapeHtml(item.class_value || '-')}</span>
                            <span class="meta-pill">${escapeHtml(item.gender || '-')}</span>
                            <span class="meta-pill">Sent: ${escapeHtml(item.created_at || '-')}</span>
                            <span class="meta-pill">Message ID: ${Number(item.id || 0)}</span>
                        </div>
                        <div class="message-box">
                            <div class="message-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M7 10h10M7 14h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path><path d="M6 19v-3.2A4.8 4.8 0 0 1 4 12c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7c-1.4 0-2.717-.315-3.86-.871L6 19Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path></svg>
                            </div>
                            <div class="message-body">
                                <div class="message-heading"><strong>Message</strong></div>
                                <p class="message-preview">${escapeHtml(item.message_text || '').replace(/\n/g, '<br>')}</p>
                            </div>
                            <div class="message-meta">${hasReply ? 'Reply updated' : 'Awaiting admin reply'}</div>
                        </div>
                        ${hasReply ? `
                            <div class="message-box" style="background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.18);">
                                <div class="message-icon" aria-hidden="true" style="background: rgba(34, 197, 94, 0.16);">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                                <div class="message-body">
                                    <div class="message-heading"><strong>Admin reply</strong></div>
                                    <p class="message-preview">${escapeHtml(item.admin_reply || '').replace(/\n/g, '<br>')}</p>
                                </div>
                                <div class="message-meta">Reply time: ${escapeHtml(item.replied_at || '-')}</div>
                            </div>` : ''}
                    </div>
                    <div class="ticket-actions">
                        <a class="view-btn" href="${orderUrl}">Open in Order Page</a>
                        <form method="post" class="delete-form" onsubmit="return confirm('Delete this message?');">
                            <input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
                            <input type="hidden" name="message_id" value="${Number(item.id || 0)}">
                            <button type="submit" name="delete_message" value="1" class="delete-btn">Delete</button>
                        </form>
                    </div>
                </article>`;
            }).join('');
            return;
        }

        if (!ticketItems.length) {
            orderMessageList.innerHTML = '';
            return;
        }

        orderMessageList.innerHTML = ticketItems.map((item) => {
            const hasReply = Boolean(item.has_reply);
            const unreadCount = Number(unreadCounts[item.id] || 0);
            const borderColor = unreadCount > 0 ? 'rgba(37, 211, 102, 0.9)' : 'rgba(109, 140, 255, 0.6)';
            const ticketUrl = `/qwe1/chat.php?id=${encodeURIComponent(item.id || '')}`;

            return `
                <article class="ticket-row card" style="padding: 18px; border-left: 4px solid ${borderColor};">
                    <div class="ticket-content">
                        <div class="ticket-title">
                            <strong>${escapeHtml(item.subject || 'Untitled Ticket')}</strong>
                            <span class="badge ${unreadCount > 0 ? 'open' : 'resolved'}">${unreadCount > 0 ? 'New Message' : 'Recent Message'}</span>
                        </div>
                        <div class="ticket-meta">
                            <span class="meta-pill">${escapeHtml(item.ticket_code || '')}</span>
                            <span class="meta-pill">Unread: ${unreadCount}</span>
                            <span class="meta-pill">Last update: ${escapeHtml(item.latest_message_at || '-')}</span>
                        </div>
                        <div class="message-box">
                            <div class="message-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M7 10h10M7 14h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path><path d="M6 19v-3.2A4.8 4.8 0 0 1 4 12c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7c-1.4 0-2.717-.315-3.86-.871L6 19Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path></svg>
                            </div>
                            <div class="message-body">
                                <div class="message-heading"><strong>Message</strong></div>
                                <p class="message-preview">${escapeHtml(item.latest_message_text || '').replace(/\n/g, '<br>')}</p>
                            </div>
                            <div class="message-meta">${unreadCount > 0 ? `${unreadCount} unread` : 'Latest ticket activity'}</div>
                        </div>
                    </div>
                    <div class="ticket-actions">
                        <a class="view-btn" href="${ticketUrl}">Open Live Chat</a>
                    </div>
                </article>`;
        }).join('');
    };

    const showBrowserNotification = (title, body) => {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }

        try {
            new Notification(title, {body});
        } catch (error) {
            // Ignore browser notification failures.
        }
    };

    const checkForUpdates = async () => {
        if (!feedUrl) {
            return;
        }

        try {
            const response = await fetch(feedUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                cache: 'no-store'
            });

            if (!response.ok) {
                return;
            }

            const nextFeed = await response.json();

            if (!nextFeed || nextFeed.signature === currentFeed.signature) {
                return;
            }

            const previousItems = getItemMap(currentFeed);
            const nextItems = Array.isArray(nextFeed.items) ? nextFeed.items : [];
            const changedItem = nextItems.find((item) => {
                const previousItem = previousItems.get(String(item.id));
                if (!previousItem) {
                    return true;
                }

                return (previousItem.latest_message_at || '') !== (item.latest_message_at || '')
                    || Number(previousItem.message_count || 0) !== Number(item.message_count || 0);
            });
            const previousOrderFeed = currentFeed.order_messages || {};
            const nextOrderFeed = nextFeed.order_messages || {};
            const orderMessageChanged = Number(previousOrderFeed.latest_id || 0) !== Number(nextOrderFeed.latest_id || 0)
                || Number(previousOrderFeed.pending_count || 0) !== Number(nextOrderFeed.pending_count || 0);

            currentFeed = nextFeed;
            updateBadges(nextFeed);
            renderOrderMessages(nextFeed);
            if (previewPanel && typeof nextFeed.preview_html === 'string') {
                previewPanel.innerHTML = nextFeed.preview_html;
            }

            if (changedItem) {
                showToast(changedItem);
                showBrowserNotification(`New message • ${changedItem.ticket_code || 'Ticket'}`, changedItem.latest_message_text || `${changedItem.customer_name || 'Customer'} sent a new message.`);
                playNotificationTone();
                return;
            }

            if (orderMessageChanged && Number(nextOrderFeed.latest_id || 0) > 0) {
                showToast({
                    ticket_code: 'Order Message',
                    latest_message_text: nextOrderFeed.latest_text || `${nextOrderFeed.latest_student_name || 'Student'} sent a new message.`,
                    customer_name: nextOrderFeed.latest_student_name || 'Student'
                });
                showBrowserNotification('New order message', nextOrderFeed.latest_text || `${nextOrderFeed.latest_student_name || 'Student'} sent a new message.`);
                renderOrderMessages(nextFeed);
                playNotificationTone();
            }
        } catch (error) {
            // Silent fail keeps the admin page usable even if polling fails.
        }
    };

    syncDashboardSoundUi();

    if (muteToggle) {
        muteToggle.addEventListener('click', () => {
            soundSettings.muted = !soundSettings.muted;
            persistDashboardSoundUi();
            syncDashboardSoundUi();
        });
    }

    if (volumeControl) {
        volumeControl.addEventListener('input', () => {
            soundSettings.volume = Number(volumeControl.value || 0.75);
            persistDashboardSoundUi();
            syncDashboardSoundUi();
        });
    }

    updateBadges(currentFeed);
    renderOrderMessages(currentFeed);
    setUnreadUi(lastUnreadCount, []);
    pollUnreadCount();

    document.querySelectorAll('.message-notification-btn').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            orderSection.scrollIntoView({behavior: 'smooth', block: 'start'});
        });
    });

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target.closest('.message-box[data-open-url]') : null;
        if (!target) {
            return;
        }
        window.location.href = target.getAttribute('data-open-url') || '#';
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/qwe1/service-worker.js').catch(() => {});
    }

    document.addEventListener('click', () => {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().catch(() => {});
        }

        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume().catch(() => {});
        }
    }, {once: true});

    window.setInterval(checkForUpdates, 5000);
    window.setInterval(pollUnreadCount, 5000);
    connectRealtime();
})();
</script>
</body>
</html>

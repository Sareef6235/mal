<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';
$admin = requireAdmin();

$status = trim((string) ($_GET['status'] ?? ''));
$priority = trim((string) ($_GET['priority'] ?? ''));
$agent = (int) ($_GET['agent'] ?? 0);

$where = [];
$params = [];
if ($status !== '') { $where[] = 't.status = :status'; $params['status'] = $status; }
if ($priority !== '') { $where[] = 't.priority = :priority'; $params['priority'] = $priority; }
if ($agent > 0) { $where[] = 't.assigned_to = :agent'; $params['agent'] = $agent; }

$sql = 'SELECT t.*, u.name AS customer_name, a.name AS agent_name
        FROM tickets t
        INNER JOIN users u ON u.id = t.user_id
        LEFT JOIN users a ON a.id = t.assigned_to';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY t.created_at DESC LIMIT 100';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$stats = [
    'total' => (int) db()->query('SELECT COUNT(*) FROM tickets')->fetchColumn(),
    'open' => (int) db()->query("SELECT COUNT(*) FROM tickets WHERE status IN ('Open','In Progress','Waiting')")->fetchColumn(),
    'avg_response_minutes' => (float) db()->query('SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, m.created_at)),0) FROM tickets t JOIN ticket_messages m ON m.ticket_id = t.id AND m.message_type = "reply"')->fetchColumn(),
];

renderHead(__('admin.dashboard'));
?>
<body>
<div style="max-width:1200px;margin:20px auto;padding:0 16px">
    <h1><?= e(__('admin.dashboard')) ?></h1>
    <p>Welcome, <?= e((string) $admin['name']) ?></p>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
        <div style="padding:16px;border:1px solid #cbd5e1;border-radius:10px">Total Tickets: <strong><?= $stats['total'] ?></strong></div>
        <div style="padding:16px;border:1px solid #cbd5e1;border-radius:10px">Open Tickets: <strong><?= $stats['open'] ?></strong></div>
        <div style="padding:16px;border:1px solid #cbd5e1;border-radius:10px">Avg. 1st Response: <strong><?= number_format($stats['avg_response_minutes'], 1) ?> min</strong></div>
    </div>

    <form method="get" style="margin:14px 0;display:flex;gap:8px;flex-wrap:wrap">
        <select name="status">
            <option value="">All Status</option><option>Open</option><option>In Progress</option><option>Waiting</option><option>Resolved</option><option>Closed</option>
        </select>
        <select name="priority">
            <option value="">All Priority</option><option>Low</option><option>Normal</option><option>High</option><option>Urgent</option>
        </select>
        <input name="agent" type="number" min="0" placeholder="Agent ID" value="<?= $agent > 0 ? $agent : '' ?>">
        <button type="submit">Filter</button>
    </form>

    <table width="100%" border="1" cellpadding="8" cellspacing="0">
        <thead><tr><th>Code</th><th>Subject</th><th>Status</th><th>Priority</th><th>Due</th><th>Assigned</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $ticket): ?>
            <tr>
                <td><?= e((string) $ticket['ticket_code']) ?></td>
                <td><?= e((string) $ticket['subject']) ?></td>
                <td><?= e((string) $ticket['status']) ?></td>
                <td><?= e((string) $ticket['priority']) ?></td>
                <td><?= e((string) $ticket['due_at']) ?></td>
                <td><?= e((string) ($ticket['agent_name'] ?? 'Unassigned')) ?></td>
                <td><a href="/admin-ticket.php?id=<?= (int) $ticket['id'] ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
<?php renderFooter(); ?>

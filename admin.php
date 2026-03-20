<?php
require __DIR__ . '/lib.php';
$admin = requireAdmin();
$flash = getFlash();
$status = trim($_GET['status'] ?? '');
$sql = 'SELECT t.*, u.name, u.email FROM tickets t INNER JOIN users u ON u.id = t.user_id';
$params = [];
if ($status !== '') {
    $sql .= ' WHERE t.status = :status';
    $params['status'] = $status;
}
$sql .= ' ORDER BY t.last_message_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Admin Panel</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Malayalam:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/style.css"></head><body><div class="dashboard-shell admin-mode"><aside class="sidebar card"><div class="brand small"><div class="brand-mark">AD</div><div><strong><?= e($admin['name']) ?></strong><p>Administrator</p></div></div><nav class="stack-links"><a href="/admin.php">All Tickets</a><a href="/admin.php?status=Open">Open</a><a href="/admin.php?status=Pending">Pending</a><a href="/admin.php?status=Resolved">Resolved</a><a href="/logout.php">Logout</a></nav></aside><main class="main-panel"><?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?><section class="panel-head"><div><span class="eyebrow">Admin secure panel</span><h1>Support ticket control room</h1><p class="muted">Review linked orders, reply to customers, and monitor notification delivery.</p></div></section><section class="ticket-list"><?php foreach ($tickets as $ticket): ?><a class="ticket-row card" href="/ticket.php?id=<?= (int) $ticket['id'] ?>"><div><strong><?= e($ticket['subject']) ?></strong><p><?= e($ticket['name']) ?> · <?= e($ticket['email']) ?> · <?= e($ticket['ticket_code']) ?></p></div><div class="align-right"><span class="badge <?= strtolower($ticket['status']) ?>"><?= e($ticket['status']) ?></span><small><?= e($ticket['priority']) ?></small></div></a><?php endforeach; ?><?php if (!$tickets): ?><div class="card empty">No tickets match this filter.</div><?php endif; ?></section></main></div></body></html>

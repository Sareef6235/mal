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
renderHead('Admin Panel', 'Admin control panel for managing customer tickets, statuses, and reply conversations.');
?>
<body>
<div class="page-shell app-page">
    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-b"></div>
    <header class="topbar glass" aria-label="Admin header">
        <div class="brand">
            <div class="brand-mark">AD</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Admin control room for ticket operations and customer communication</p>
            </div>
        </div>
        <nav class="menu" aria-label="Admin navigation">
            <a href="/">Home</a>
            <a href="/admin.php">All Tickets</a>
            <a href="/logout.php">Logout</a>
        </nav>
    </header>

    <main class="dashboard-shell admin-mode">
        <aside class="sidebar card" aria-label="Admin sidebar">
            <div class="brand small">
                <div class="brand-mark">AD</div>
                <div>
                    <strong><?= e($admin['name']) ?></strong>
                    <p><?= e($admin['email']) ?></p>
                </div>
            </div>
            <nav class="stack-links" aria-label="Admin filters">
                <a href="/admin.php">All Tickets</a>
                <a href="/admin.php?status=Open">Open</a>
                <a href="/admin.php?status=Pending">Pending</a>
                <a href="/admin.php?status=Resolved">Resolved</a>
                <a href="/logout.php">Logout</a>
            </nav>
        </aside>

        <section class="main-panel">
            <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
            <header class="panel-head card soft-panel">
                <div>
                    <span class="eyebrow">Admin secure panel</span>
                    <h1>Support ticket control room</h1>
                    <p class="muted">Review order-linked tickets, respond in chat format, and monitor customer communication from one premium admin workspace.</p>
                </div>
                <div class="hero-actions">
                    <a class="secondary-btn" href="/">Landing</a>
                </div>
            </header>

            <section class="ticket-list" aria-label="Admin tickets">
                <?php foreach ($tickets as $ticket): ?>
                    <a class="ticket-row card" href="/ticket.php?id=<?= (int) $ticket['id'] ?>" aria-label="Open admin ticket <?= e($ticket['ticket_code']) ?>">
                        <div>
                            <strong><?= e($ticket['subject']) ?></strong>
                            <p><?= e($ticket['name']) ?> · <?= e($ticket['email']) ?> · <?= e($ticket['ticket_code']) ?></p>
                        </div>
                        <div class="align-right">
                            <span class="badge <?= strtolower($ticket['status']) ?>"><?= e($ticket['status']) ?></span>
                            <small><?= e($ticket['priority']) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$tickets): ?><div class="card empty">No tickets match this filter.</div><?php endif; ?>
            </section>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
</body>
</html>

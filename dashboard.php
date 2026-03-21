<?php
require __DIR__ . '/lib.php';
$user = requireLogin();
$flash = getFlash();
$stmt = db()->prepare('SELECT * FROM tickets WHERE user_id = :user_id ORDER BY updated_at DESC');
$stmt->execute(['user_id' => $user['id']]);
$tickets = $stmt->fetchAll();
renderHead('Customer Dashboard', 'Customer dashboard for tracking support tickets, replies, and order-linked conversations.');
?>
<body>
<div class="page-shell app-page">
    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-b"></div>
    <header class="topbar glass" aria-label="Customer header">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Customer workspace for active conversations and ticket tracking</p>
            </div>
        </div>
        <nav class="menu" aria-label="Customer navigation">
            <a href="/">Home</a>
            <a href="/new-ticket.php">New Ticket</a>
            <a href="/logout.php">Logout</a>
        </nav>
    </header>

    <main class="dashboard-shell">
        <aside class="sidebar card" aria-label="Customer sidebar">
            <div class="brand small">
                <div class="brand-mark">PS</div>
                <div>
                    <strong><?= e($user['name']) ?></strong>
                    <p><?= e($user['email']) ?></p>
                </div>
            </div>
            <a class="primary-btn full" href="/new-ticket.php">+ New Ticket</a>
            <nav class="stack-links" aria-label="Customer sidebar navigation">
                <a href="/dashboard.php">My Tickets</a>
                <a href="/">Home</a>
                <a href="/logout.php">Logout</a>
            </nav>
        </aside>

        <section class="main-panel">
            <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
            <header class="panel-head card soft-panel">
                <div>
                    <span class="eyebrow">Customer workspace</span>
                    <h1>Your support conversations</h1>
                    <p class="muted">Track every ticket, reply, order reference, and status update from a single responsive premium dashboard.</p>
                </div>
                <div class="hero-actions">
                    <a class="primary-btn" href="/new-ticket.php">Create Ticket</a>
                    <a class="secondary-btn" href="/">View Home</a>
                </div>
            </header>

            <section class="ticket-list" aria-label="Customer tickets">
                <?php foreach ($tickets as $ticket): ?>
                    <a class="ticket-row card" href="/ticket.php?id=<?= (int) $ticket['id'] ?>" aria-label="Open ticket <?= e($ticket['ticket_code']) ?>">
                        <div>
                            <strong><?= e($ticket['subject']) ?></strong>
                            <p><?= e($ticket['ticket_code']) ?> · <?= e($ticket['category']) ?> · Order <?= e((string) $ticket['order_reference']) ?></p>
                        </div>
                        <div class="align-right">
                            <span class="badge <?= strtolower($ticket['status']) ?>"><?= e($ticket['status']) ?></span>
                            <small><?= e($ticket['updated_at']) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (!$tickets): ?><div class="card empty">No tickets yet. Create your first premium support request.</div><?php endif; ?>
            </section>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
</body>
</html>

<?php
require __DIR__ . '/lib.php';
$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $orderReference = trim($_POST['order_reference'] ?? '');
    $category = trim($_POST['category'] ?? 'General Support');
    $priority = trim($_POST['priority'] ?? 'Normal');

    if ($subject === '' || $message === '') {
        flash('error', 'Subject and message are required.');
        redirect('/new-ticket.php');
    }

    $ticketCode = generateTicketCode();
    $stmt = db()->prepare('INSERT INTO tickets (ticket_code, user_id, subject, order_reference, category, priority, status) VALUES (:ticket_code, :user_id, :subject, :order_reference, :category, :priority, :status)');
    $stmt->execute([
        'ticket_code' => $ticketCode,
        'user_id' => $user['id'],
        'subject' => $subject,
        'order_reference' => $orderReference !== '' ? $orderReference : null,
        'category' => $category,
        'priority' => $priority,
        'status' => 'Open',
    ]);

    $ticketId = (int) db()->lastInsertId();
    createMessage($ticketId, (int) $user['id'], $message, 'web');

    $adminEmailResult = sendEmailNotification((string) config('email.admin_to'), 'New Ticket: ' . $subject, $message);
    queueNotification($ticketId, 'email', (string) config('email.admin_to'), ['subject' => $subject, 'message' => $message], $adminEmailResult['status'], $adminEmailResult['response']);

    $targetPhone = $user['phone'] !== '' ? $user['phone'] : (string) config('whatsapp.phone');
    $waResult = sendWhatsAppNotification($targetPhone, 'Ticket ' . $ticketCode . ' created: ' . $subject);
    queueNotification($ticketId, 'whatsapp', $targetPhone, ['message' => 'Ticket created'], $waResult['status'], $waResult['response']);

    flash('success', 'Ticket created successfully.');
    redirect('/ticket.php?id=' . $ticketId);
}

$flash = getFlash();
renderHead('Create Ticket', 'Create a support ticket with subject, priority, category, and detailed message.');
?>
<body>
<div class="page-shell auth-page">
    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-b"></div>
    <header class="topbar glass" aria-label="Create ticket header">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Submit a structured support request with order reference and priority</p>
            </div>
        </div>
        <nav class="menu" aria-label="Create ticket navigation">
            <a href="/dashboard.php">Dashboard</a>
            <a href="/logout.php">Logout</a>
        </nav>
    </header>

    <main class="auth-shell">
        <section class="auth-card card large" aria-labelledby="ticket-create-title">
            <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
            <span class="eyebrow">Create support ticket</span>
            <h1 id="ticket-create-title">Send a new message</h1>
            <p class="muted">Use this premium form to open a ticket, connect an order, and explain your issue with full context.</p>
            <form class="stack-form" method="post" aria-label="Create ticket form">
                <label class="field-label">Subject
                    <input name="subject" placeholder="Subject" required aria-label="Ticket subject">
                </label>
                <div class="split-grid">
                    <label class="field-label">Order Reference
                        <input name="order_reference" placeholder="Order Reference (optional)" aria-label="Order reference">
                    </label>
                    <label class="field-label">Priority
                        <select name="priority" aria-label="Priority">
                            <option>Low</option>
                            <option selected>Normal</option>
                            <option>High</option>
                            <option>Urgent</option>
                        </select>
                    </label>
                </div>
                <label class="field-label">Category
                    <input name="category" placeholder="Category" value="General Support" aria-label="Category">
                </label>
                <label class="field-label">Message
                    <textarea name="message" rows="8" placeholder="Describe your issue in detail" required aria-label="Message"></textarea>
                </label>
                <button class="primary-btn" type="submit">Create Ticket</button>
            </form>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
</body>
</html>

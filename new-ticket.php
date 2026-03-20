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
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Create Ticket</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Malayalam:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/style.css"></head><body><div class="auth-shell"><div class="auth-card card large"><?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?><span class="eyebrow">Create support ticket</span><h1>Send new message</h1><form class="stack-form" method="post"><input name="subject" placeholder="Subject" required><div class="split-grid"><input name="order_reference" placeholder="Order Reference (optional)"><select name="priority"><option>Low</option><option selected>Normal</option><option>High</option><option>Urgent</option></select></div><input name="category" placeholder="Category" value="General Support"><textarea name="message" rows="8" placeholder="Describe your issue in detail" required></textarea><button class="primary-btn" type="submit">Create Ticket</button></form></div></div></body></html>

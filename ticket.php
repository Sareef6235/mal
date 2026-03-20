<?php
require __DIR__ . '/lib.php';
$user = requireLogin();
$ticketId = (int) ($_GET['id'] ?? 0);
$ticket = ticketWithMessages($ticketId, $user);
if (!$ticket) {
    flash('error', 'Ticket not found.');
    redirect($user['role'] === 'admin' ? '/admin.php' : '/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    if ($message !== '') {
        createMessage($ticketId, (int) $user['id'], $message, 'web');
        $emailResult = sendEmailNotification($ticket['email'], 'Ticket update: ' . $ticket['subject'], $message);
        queueNotification($ticketId, 'email', $ticket['email'], ['message' => $message], $emailResult['status'], $emailResult['response']);

        $phone = $ticket['phone'] ?: (string) config('whatsapp.phone');
        $waResult = sendWhatsAppNotification($phone, $message);
        queueNotification($ticketId, 'whatsapp', $phone, ['message' => $message], $waResult['status'], $waResult['response']);

        if ($user['role'] === 'admin' && isset($_POST['status'])) {
            $update = db()->prepare('UPDATE tickets SET status = :status WHERE id = :id');
            $update->execute(['status' => $_POST['status'], 'id' => $ticketId]);
        }

        flash('success', 'Reply sent successfully.');
    }

    redirect('/ticket.php?id=' . $ticketId);
}

$flash = getFlash();
$notifications = db()->prepare('SELECT * FROM notifications WHERE ticket_id = :ticket_id ORDER BY created_at DESC LIMIT 8');
$notifications->execute(['ticket_id' => $ticketId]);
$notificationRows = $notifications->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= e($ticket['subject']) ?></title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Malayalam:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/style.css"></head><body><div class="chat-layout"><aside class="chat-sidebar card"><a href="<?= $user['role'] === 'admin' ? '/admin.php' : '/dashboard.php' ?>" class="secondary-btn">← Back</a><div class="ticket-meta"><h2><?= e($ticket['subject']) ?></h2><p><?= e($ticket['ticket_code']) ?></p><span class="badge <?= strtolower($ticket['status']) ?>"><?= e($ticket['status']) ?></span></div><div class="meta-list"><div><strong>Customer</strong><span><?= e($ticket['name']) ?></span></div><div><strong>Email</strong><span><?= e($ticket['email']) ?></span></div><div><strong>Order</strong><span><?= e((string) $ticket['order_reference']) ?></span></div><div><strong>Priority</strong><span><?= e($ticket['priority']) ?></span></div></div><h3>Recent notifications</h3><div class="notification-list"><?php foreach ($notificationRows as $row): ?><div class="notify-item"><strong><?= e(strtoupper($row['type'])) ?></strong><span><?= e($row['status']) ?></span><small><?= e($row['response_text'] ?: 'No response') ?></small></div><?php endforeach; ?><?php if (!$notificationRows): ?><p class="muted">No notifications logged yet.</p><?php endif; ?></div></aside><main class="chat-panel card"><?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?><div class="chat-header"><div><span class="eyebrow">WhatsApp-style chat UI</span><h1><?= e($ticket['subject']) ?></h1></div><?php if ($user['role'] === 'admin'): ?><form method="post" class="status-form"><input type="hidden" name="message" value="Status updated by admin."><select name="status"><option<?= $ticket['status'] === 'Open' ? ' selected' : '' ?>>Open</option><option<?= $ticket['status'] === 'Pending' ? ' selected' : '' ?>>Pending</option><option<?= $ticket['status'] === 'Resolved' ? ' selected' : '' ?>>Resolved</option><option<?= $ticket['status'] === 'Closed' ? ' selected' : '' ?>>Closed</option></select><button class="secondary-btn" type="submit">Update</button></form><?php endif; ?></div><div class="chat-stream"><?php foreach ($ticket['messages'] as $message): ?><div class="msg <?= $message['role'] === 'admin' ? 'agent' : 'customer' ?>"><div class="msg-head"><strong><?= e($message['name']) ?></strong><span><?= e($message['created_at']) ?></span></div><p><?= nl2br(e($message['message'])) ?></p><small><?= e($message['channel']) ?></small></div><?php endforeach; ?></div><form method="post" class="composer"><textarea name="message" rows="3" placeholder="Type your message..." required></textarea><?php if ($user['role'] === 'admin'): ?><select name="status"><option<?= $ticket['status'] === 'Open' ? ' selected' : '' ?>>Open</option><option<?= $ticket['status'] === 'Pending' ? ' selected' : '' ?>>Pending</option><option<?= $ticket['status'] === 'Resolved' ? ' selected' : '' ?>>Resolved</option><option<?= $ticket['status'] === 'Closed' ? ' selected' : '' ?>>Closed</option></select><?php endif; ?><button class="primary-btn" type="submit">Send Reply</button></form></main></div></body></html>

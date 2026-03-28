<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';
$admin = requireAdmin();
$ticketId = (int) ($_GET['id'] ?? 0);

if ($ticketId <= 0) {
    http_response_code(404);
    exit('Ticket not found');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken('admin_ticket', (string) ($_POST['_csrf'] ?? ''))) {
        flash('error', 'Invalid CSRF token');
        redirect('/admin-ticket.php?id=' . $ticketId);
    }

    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'status') {
            updateTicketStatus($ticketId, (string) $_POST['status'], (int) $admin['id']);
        } elseif ($action === 'assign') {
            assignTicket($ticketId, (int) $_POST['assigned_to'], (int) $admin['id']);
        } elseif ($action === 'reply') {
            $msg = trim((string) $_POST['message']);
            if ($msg !== '') {
                createMessage($ticketId, (int) $admin['id'], $msg, 'web', 'reply', false);
                logAudit($ticketId, (int) $admin['id'], 'reply_added', []);
            }
        } elseif ($action === 'note') {
            $note = trim((string) $_POST['note']);
            if ($note !== '') {
                createMessage($ticketId, (int) $admin['id'], $note, 'web', 'note', true);
                logAudit($ticketId, (int) $admin['id'], 'internal_note_added', []);
            }
        }
        flash('success', 'Updated');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }

    redirect('/admin-ticket.php?id=' . $ticketId);
}

$stmt = db()->prepare('SELECT t.*, u.name AS customer_name FROM tickets t JOIN users u ON u.id = t.user_id WHERE t.id = :id');
$stmt->execute(['id' => $ticketId]);
$ticket = $stmt->fetch();
if (!$ticket) {
    http_response_code(404);
    exit('Ticket not found');
}

$msgStmt = db()->prepare('SELECT m.*, u.name FROM ticket_messages m JOIN users u ON u.id = m.user_id WHERE m.ticket_id = :ticket_id ORDER BY m.created_at ASC');
$msgStmt->execute(['ticket_id' => $ticketId]);
$messages = $msgStmt->fetchAll();

$canned = db()->query('SELECT id, title, body FROM canned_responses WHERE is_active = 1 ORDER BY title')->fetchAll();
$agents = db()->query("SELECT id, name FROM users WHERE role IN ('admin','agent') ORDER BY name")->fetchAll();
$flash = getFlash();
$csrf = generateCsrfToken('admin_ticket');
renderHead('Ticket #' . $ticketId);
?>
<body>
<div style="max-width:1100px;margin:20px auto;padding:0 16px;display:grid;grid-template-columns:2fr 1fr;gap:14px">
    <section style="border:1px solid #cbd5e1;border-radius:12px;padding:14px">
        <?php if ($flash): ?><div><?= e((string) $flash['message']) ?></div><?php endif; ?>
        <h2><?= e((string) $ticket['subject']) ?></h2>
        <p>Status: <strong><?= e((string) $ticket['status']) ?></strong> | Priority: <strong><?= e((string) $ticket['priority']) ?></strong></p>
        <p>Customer: <?= e((string) $ticket['customer_name']) ?></p>

        <h3>Conversation</h3>
        <?php foreach ($messages as $m): ?>
            <?php if ((int) $m['is_internal'] === 1): ?>
                <div style="padding:10px;border-left:4px solid #f59e0b;background:#fff7ed;margin-bottom:8px">
                    <strong>Internal Note by <?= e((string) $m['name']) ?></strong>
                    <p><?= nl2br(e((string) $m['message'])) ?></p>
                </div>
            <?php else: ?>
                <div style="padding:10px;border-left:4px solid #3b82f6;background:#eff6ff;margin-bottom:8px">
                    <strong><?= e((string) $m['name']) ?> (<?= e((string) $m['message_type']) ?>)</strong>
                    <p><?= nl2br(e((string) $m['message'])) ?></p>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="reply">
            <label>Reply
                <textarea id="replyBox" name="message" rows="5" style="width:100%"></textarea>
            </label>
            <label>Canned Response
                <select id="cannedPick">
                    <option value="">Select...</option>
                    <?php foreach ($canned as $row): ?>
                        <option value="<?= e((string) $row['body']) ?>"><?= e((string) $row['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Send Reply</button>
        </form>

        <form method="post" style="margin-top:12px">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="note">
            <label>Internal Note (Admin only)
                <textarea name="note" rows="3" style="width:100%"></textarea>
            </label>
            <button type="submit">Add Note</button>
        </form>
    </section>

    <aside style="border:1px solid #cbd5e1;border-radius:12px;padding:14px">
        <h3>Manage Ticket</h3>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="status">
            <select name="status">
                <option>Open</option><option>In Progress</option><option>Waiting</option><option>Resolved</option><option>Closed</option>
            </select>
            <button type="submit">Update Status</button>
        </form>

        <form method="post" style="margin-top:10px">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="assign">
            <select name="assigned_to">
                <?php foreach ($agents as $a): ?>
                    <option value="<?= (int) $a['id'] ?>"><?= e((string) $a['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Assign Agent</button>
        </form>
    </aside>
</div>

<script>
document.getElementById('cannedPick').addEventListener('change', (e) => {
    if (e.target.value) {
        document.getElementById('replyBox').value = e.target.value;
    }
});
</script>
</body>
<?php renderFooter(); ?>

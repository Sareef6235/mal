<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/lib.php';

const TICKET_ATTACHMENT_TOKEN_PATTERN = '/\[\[attachment:(.*?)\]\]/';

function extractTicketMessage(string $message): array
{
    preg_match_all(TICKET_ATTACHMENT_TOKEN_PATTERN, $message, $matches);
    $attachments = [];

    foreach ($matches[1] ?? [] as $token) {
        $decoded = json_decode(base64_decode((string) $token, true) ?: '', true);
        if (is_array($decoded) && isset($decoded['path'], $decoded['type'])) {
            $attachments[] = $decoded;
        }
    }

    $clean = trim((string) preg_replace(TICKET_ATTACHMENT_TOKEN_PATTERN, '', $message));
    return [$clean, $attachments];
}

function renderTicketMessages(array $ticket, array $user, int $ticketId): string
{
    ob_start();
    foreach ((array) ($ticket['messages'] ?? []) as $message) {
        [$text, $attachments] = extractTicketMessage((string) ($message['message'] ?? ''));
        $isMine = (int) ($message['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
        $role = (string) ($message['role'] ?? (($isMine && (($user['role'] ?? '') === 'admin')) ? 'admin' : 'user'));
        ?>
        <article class="msg <?= $role === 'admin' ? 'agent' : 'customer' ?>" id="message-<?= (int) ($message['id'] ?? 0) ?>">
            <div class="meta">
                <strong><?= e((string) ($message['name'] ?? ($role === 'admin' ? 'Admin' : 'Customer'))) ?></strong>
                <span><?= e((string) ($message['created_at'] ?? '')) ?></span>
            </div>
            <?php if ($text !== ''): ?><p><?= nl2br(e($text)) ?></p><?php endif; ?>
            <?php foreach ($attachments as $attachment): ?>
                <div class="attachment"><a href="<?= e((string) ($attachment['path'] ?? '#')) ?>" target="_blank" rel="noopener"><?= e((string) ($attachment['name'] ?? 'Attachment')) ?></a></div>
            <?php endforeach; ?>
        </article>
        <?php
    }

    return (string) ob_get_clean();
}

$user = requireLogin();
$ticketId = (int) ($_GET['id'] ?? 0);
$ticket = ticketWithMessages($ticketId, $user);
if (!$ticket) {
    flash('error', 'Ticket not found.');
    redirect(($user['role'] ?? '') === 'admin' ? '/qwe1/admin.php' : '/qwe1/dashboard.php');
}

if (($_GET['partial'] ?? '') === 'stream') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'messages_html' => renderTicketMessages($ticket, $user, $ticketId),
        'last_message_id' => (int) (($ticket['messages'][array_key_last($ticket['messages'])]['id'] ?? 0)),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim((string) ($_POST['message'] ?? ''));
    if ($message === '') {
        flash('error', 'Message cannot be empty.');
        redirect('/qwe1/ticket.php?id=' . $ticketId);
    }

    $source = (($user['role'] ?? '') === 'admin') ? 'admin' : 'web';
    createMessage($ticketId, (int) ($user['id'] ?? 0), $message, $source);
    flash('success', 'Reply sent successfully.');
    redirect('/qwe1/ticket.php?id=' . $ticketId);
}

$ticket = ticketWithMessages($ticketId, $user) ?: $ticket;
$flash = getFlash();
renderHead('Ticket Conversation', 'Ticket chat page linked with realtime chat stream.');
?>
<body>
<div style="max-width:1000px;margin:24px auto;padding:16px;color:#e2e8f0;background:#0f172a;font-family:Segoe UI,sans-serif;">
    <header style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:16px;">
        <div>
            <h1 style="margin:0;"><?= e((string) ($ticket['subject'] ?? 'Ticket')) ?></h1>
            <small><?= e((string) ($ticket['ticket_code'] ?? ('#' . $ticketId))) ?></small>
        </div>
        <nav style="display:flex;gap:10px;">
            <a href="/qwe1/chat.php?id=<?= $ticketId ?>" style="color:#93c5fd;">Open Realtime Chat</a>
            <a href="<?= ($user['role'] ?? '') === 'admin' ? '/qwe1/admin.php' : '/qwe1/dashboard.php' ?>" style="color:#93c5fd;">Back</a>
        </nav>
    </header>

    <?php if (!empty($flash)): ?>
        <div style="margin-bottom:12px;padding:10px;border-radius:8px;background:<?= ($flash['type'] ?? '') === 'error' ? '#7f1d1d' : '#14532d' ?>;">
            <?= e((string) ($flash['message'] ?? '')) ?>
        </div>
    <?php endif; ?>

    <section id="chat-stream" style="display:grid;gap:10px;max-height:60vh;overflow:auto;padding:10px;border:1px solid rgba(255,255,255,.15);border-radius:12px;">
        <?= renderTicketMessages($ticket, $user, $ticketId) ?>
    </section>

    <form method="post" style="margin-top:12px;display:grid;grid-template-columns:1fr auto;gap:10px;">
        <textarea name="message" rows="3" placeholder="Type your message..." style="padding:10px;border-radius:10px;"></textarea>
        <button type="submit" style="padding:10px 16px;border-radius:10px;background:#2563eb;color:#fff;border:none;">Send</button>
    </form>
</div>

<script>
(() => {
    const stream = document.getElementById('chat-stream');
    if (!stream) return;

    let busy = false;
    let lastMessageId = Number((stream.querySelector('.msg:last-of-type')?.id || 'message-0').replace('message-', '')) || 0;

    const refresh = async () => {
        if (busy) return;
        busy = true;
        try {
            const response = await fetch(`/qwe1/ticket.php?id=<?= $ticketId ?>&partial=stream`, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) {
                throw new Error('Failed stream refresh');
            }
            const data = await response.json();
            if (typeof data.messages_html === 'string') {
                stream.innerHTML = data.messages_html;
            }
            lastMessageId = Math.max(lastMessageId, Number(data.last_message_id || 0));
        } catch (error) {
            console.error(error);
        } finally {
            busy = false;
        }
    };

    window.setInterval(refresh, 5000);
})();
</script>
</body>
</html>

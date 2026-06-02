<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/lib/ImapClient.php';
$config = require __DIR__ . '/config/mailboxes.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'messages';
$accountKey = $_GET['account'] ?? $_POST['account'] ?? $config['default'];
$account = $config['accounts'][$accountKey] ?? $config['accounts'][$config['default']];

try {
    if (!extension_loaded('imap') || ($account['imap']['password'] ?? '') === '' || ($account['imap']['password'] ?? '') === 'change-me') {
        respond(mockResponse($action));
    }

    $client = new ImapClient($account);
    $folder = strtolower((string) ($_GET['folder'] ?? $_POST['folder'] ?? 'inbox'));
    $uid = (int) ($_GET['uid'] ?? $_POST['uid'] ?? 0);

    $payload = match ($action) {
        'messages' => $client->listMessages(
            $folder,
            max(1, (int) ($_GET['page'] ?? 1)),
            min(50, max(5, (int) ($_GET['limit'] ?? 20))),
            trim((string) ($_GET['q'] ?? ''))
        ),
        'message' => $client->getMessage($folder, $uid),
        'delete' => ['ok' => $client->delete($folder, $uid)],
        'flag' => ['ok' => $client->flag($folder, $uid, (string) ($_POST['flag'] ?? 'read'), filter_var($_POST['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN))],
        'stats' => $client->stats(),
        'accounts' => ['accounts' => array_values(array_map(fn (array $item): array => ['email' => $item['email'], 'label' => $item['label']], $config['accounts']))],
        default => throw new InvalidArgumentException('Unknown API action.'),
    };

    respond($payload);
} catch (Throwable $error) {
    http_response_code(500);
    respond(['error' => true, 'message' => $error->getMessage()]);
}

function respond(array $payload): never
{
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function mockResponse(string $action): array
{
    $messages = [
        [
            'uid' => 101,
            'sender' => 'Maya from Support Ops',
            'senderEmail' => 'maya@example.com',
            'subject' => 'Priority customer onboarding checklist',
            'preview' => 'Here is the refreshed VIP onboarding checklist with the SLA milestones, account notes, and attachment review steps.',
            'time' => 'Today, 9:42 AM',
            'unread' => true,
            'starred' => true,
            'hasAttachments' => true,
        ],
        [
            'uid' => 102,
            'sender' => 'cPanel Monitor',
            'senderEmail' => 'monitor@mmhnu.online',
            'subject' => 'Mailbox storage is healthy',
            'preview' => 'Your help mailbox is using 32% of its quota. No action is required at this moment.',
            'time' => 'Yesterday',
            'unread' => false,
            'starred' => false,
            'hasAttachments' => false,
        ],
        [
            'uid' => 103,
            'sender' => 'Aarav Mehta',
            'senderEmail' => 'aarav@example.org',
            'subject' => 'Invoice PDF and updated service agreement',
            'preview' => 'Please review the attached invoice and signed service agreement. We can proceed after your confirmation.',
            'time' => 'May 31',
            'unread' => true,
            'starred' => false,
            'hasAttachments' => true,
        ],
    ];

    return match ($action) {
        'message' => [
            ...$messages[0],
            'html' => '<p>Hello team,</p><p>This premium mail viewer is running in demo mode until IMAP credentials are configured. The production API will render the selected email body here.</p><p>Regards,<br>Maya</p>',
            'text' => 'Demo mode message body.',
            'body' => '<p>Hello team,</p><p>This premium mail viewer is running in demo mode until IMAP credentials are configured. The production API will render the selected email body here.</p><p>Regards,<br>Maya</p>',
            'attachments' => [
                ['name' => 'onboarding-checklist.pdf', 'part' => '2', 'size' => 248000, 'type' => 'pdf'],
                ['name' => 'sla-timeline.png', 'part' => '3', 'size' => 142000, 'type' => 'png'],
            ],
        ],
        'stats' => ['total' => 1284, 'unread' => 18, 'sent' => 342, 'uptime' => date('M j, H:i')],
        'accounts' => ['accounts' => [['email' => 'help@mmhnu.online', 'label' => 'MMHNU Help Desk']]],
        'delete', 'flag' => ['ok' => true, 'demo' => true],
        default => ['messages' => $messages, 'page' => 1, 'limit' => 20, 'total' => 3, 'mailboxTotal' => 1284, 'hasMore' => false, 'demo' => true],
    };
}

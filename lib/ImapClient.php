<?php

declare(strict_types=1);

final class ImapClient
{
    private array $account;
    /** @var resource|null */
    private $connection = null;
    private string $mailbox = 'INBOX';

    private const FOLDER_MAP = [
        'inbox' => 'INBOX',
        'sent' => 'Sent',
        'drafts' => 'Drafts',
        'spam' => 'Junk',
        'trash' => 'Trash',
        'starred' => 'INBOX',
    ];

    public function __construct(array $account)
    {
        $this->account = $account;
    }

    public function listMessages(string $folder = 'inbox', int $page = 1, int $limit = 20, string $query = ''): array
    {
        $this->open($folder);
        $total = imap_num_msg($this->connection) ?: 0;
        $criteria = $query !== '' ? 'TEXT "' . addcslashes($query, '"') . '"' : 'ALL';
        $ids = imap_search($this->connection, $criteria, SE_UID) ?: [];
        rsort($ids, SORT_NUMERIC);

        if ($folder === 'starred') {
            $ids = array_values(array_filter($ids, function (int $uid): bool {
                $overview = imap_fetch_overview($this->connection, (string) $uid, FT_UID)[0] ?? null;
                return !empty($overview->flagged);
            }));
        }

        $offset = max(0, ($page - 1) * $limit);
        $slice = array_slice($ids, $offset, $limit);
        $messages = array_map(fn (int $uid): array => $this->summary($uid), $slice);

        return [
            'messages' => $messages,
            'page' => $page,
            'limit' => $limit,
            'total' => count($ids),
            'mailboxTotal' => $total,
            'hasMore' => ($offset + $limit) < count($ids),
        ];
    }

    public function getMessage(string $folder, int $uid): array
    {
        $this->open($folder);
        imap_setflag_full($this->connection, (string) $uid, '\\Seen', ST_UID);
        $overview = imap_fetch_overview($this->connection, (string) $uid, FT_UID)[0] ?? null;
        $structure = imap_fetchstructure($this->connection, $uid, FT_UID);
        $parts = $this->extractParts($uid, $structure);

        return [
            ...$this->summary($uid, $overview),
            'html' => $parts['html'],
            'text' => $parts['text'],
            'body' => $parts['html'] ?: nl2br(htmlspecialchars($parts['text'] ?: 'No message body available.')),
            'attachments' => $parts['attachments'],
        ];
    }

    public function stats(): array
    {
        $this->open('inbox');
        $total = imap_num_msg($this->connection) ?: 0;
        $unread = count(imap_search($this->connection, 'UNSEEN', SE_UID) ?: []);
        $this->open('sent');
        $sent = imap_num_msg($this->connection) ?: 0;

        return [
            'total' => $total,
            'unread' => $unread,
            'sent' => $sent,
            'uptime' => date('M j, H:i'),
        ];
    }

    public function delete(string $folder, int $uid): bool
    {
        $this->open($folder);
        imap_delete($this->connection, (string) $uid, FT_UID);
        return imap_expunge($this->connection);
    }

    public function flag(string $folder, int $uid, string $flag, bool $enabled): bool
    {
        $this->open($folder);
        $imapFlag = match ($flag) {
            'star' => '\\Flagged',
            'read' => '\\Seen',
            default => '\\Seen',
        };
        return $enabled
            ? imap_setflag_full($this->connection, (string) $uid, $imapFlag, ST_UID)
            : imap_clearflag_full($this->connection, (string) $uid, $imapFlag, ST_UID);
    }

    private function open(string $folder): void
    {
        $target = self::FOLDER_MAP[$folder] ?? 'INBOX';
        if ($this->connection && $this->mailbox === $target) {
            return;
        }
        if ($this->connection) {
            imap_close($this->connection);
        }
        $imap = $this->account['imap'];
        $mailbox = sprintf('{%s:%d%s}%s', $imap['host'], $imap['port'], $imap['flags'], $target);
        if (!extension_loaded('imap')) {
            throw new RuntimeException('PHP IMAP extension is not installed.');
        }
        $this->connection = @imap_open($mailbox, $imap['username'], $imap['password'], 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
        if (!$this->connection) {
            throw new RuntimeException('Unable to connect to mailbox. Check IMAP host, credentials, and PHP IMAP extension.');
        }
        $this->mailbox = $target;
    }

    private function summary(int $uid, mixed $overview = null): array
    {
        $overview = $overview ?: (imap_fetch_overview($this->connection, (string) $uid, FT_UID)[0] ?? null);
        $from = $this->decode($overview->from ?? 'Unknown Sender');
        $subject = $this->decode($overview->subject ?? '(No subject)');
        $preview = trim(strip_tags(quoted_printable_decode(imap_fetchbody($this->connection, $uid, '1', FT_UID | FT_PEEK) ?: '')));

        return [
            'uid' => $uid,
            'sender' => $from,
            'senderEmail' => $this->extractEmail($from),
            'subject' => $subject,
            'preview' => mb_substr(preg_replace('/\s+/', ' ', $preview), 0, 160),
            'time' => isset($overview->date) ? date('M j, g:i A', strtotime($overview->date)) : '',
            'unread' => empty($overview->seen),
            'starred' => !empty($overview->flagged),
            'hasAttachments' => !empty($overview->flagged),
        ];
    }

    private function extractParts(int $uid, object|false $structure, string $partNumber = ''): array
    {
        $result = ['html' => '', 'text' => '', 'attachments' => []];
        if (!$structure) {
            $result['text'] = imap_body($this->connection, $uid, FT_UID | FT_PEEK) ?: '';
            return $result;
        }

        $parts = $structure->parts ?? [$structure];
        foreach ($parts as $index => $part) {
            $number = $partNumber === '' ? (string) ($index + 1) : $partNumber . '.' . ($index + 1);
            $isAttachment = false;
            $filename = '';
            foreach (array_merge($part->parameters ?? [], $part->dparameters ?? []) as $param) {
                if (in_array(strtolower($param->attribute ?? ''), ['filename', 'name'], true)) {
                    $isAttachment = true;
                    $filename = $this->decode($param->value ?? 'attachment');
                }
            }

            if (!empty($part->parts)) {
                $nested = $this->extractParts($uid, $part, $number);
                $result['html'] .= $nested['html'];
                $result['text'] .= $nested['text'];
                $result['attachments'] = array_merge($result['attachments'], $nested['attachments']);
                continue;
            }

            $body = imap_fetchbody($this->connection, $uid, $number, FT_UID | FT_PEEK) ?: '';
            $body = match ((int) ($part->encoding ?? 0)) {
                3 => base64_decode($body) ?: '',
                4 => quoted_printable_decode($body),
                default => $body,
            };

            if ($isAttachment) {
                $result['attachments'][] = [
                    'name' => $filename ?: 'attachment',
                    'part' => $number,
                    'size' => (int) ($part->bytes ?? strlen($body)),
                    'type' => strtolower($part->subtype ?? 'file'),
                ];
            } elseif (($part->subtype ?? '') === 'HTML') {
                $result['html'] .= $body;
            } elseif (($part->subtype ?? '') === 'PLAIN') {
                $result['text'] .= $body;
            }
        }

        return $result;
    }

    private function decode(string $value): string
    {
        $decoded = imap_mime_header_decode($value);
        return trim(implode('', array_map(fn ($part): string => $part->text, $decoded))) ?: $value;
    }

    private function extractEmail(string $from): string
    {
        return preg_match('/<([^>]+)>/', $from, $matches) ? $matches[1] : $from;
    }
}

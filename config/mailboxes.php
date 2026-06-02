<?php

declare(strict_types=1);

function env_value(string $key, ?string $default = null): ?string
{
    static $loaded = false;

    if (!$loaded) {
        $path = dirname(__DIR__) . '/.env';
        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = array_map('trim', explode('=', $line, 2));
                if (getenv($name) === false) {
                    putenv($name . '=' . trim($value, "\"'"));
                }
            }
        }
        $loaded = true;
    }

    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

return [
    'default' => env_value('WEBMAIL_DEFAULT_EMAIL', 'help@mmhnu.online'),
    'accounts' => [
        'help@mmhnu.online' => [
            'label' => 'MMHNU Help Desk',
            'email' => 'help@mmhnu.online',
            'imap' => [
                'host' => env_value('WEBMAIL_IMAP_HOST', 'mail.mmhnu.online'),
                'port' => (int) env_value('WEBMAIL_IMAP_PORT', '993'),
                'flags' => env_value('WEBMAIL_IMAP_FLAGS', '/imap/ssl/novalidate-cert'),
                'username' => env_value('WEBMAIL_IMAP_USER', 'help@mmhnu.online'),
                'password' => env_value('WEBMAIL_IMAP_PASS', ''),
            ],
            'smtp' => [
                'host' => env_value('WEBMAIL_SMTP_HOST', 'mail.mmhnu.online'),
                'port' => (int) env_value('WEBMAIL_SMTP_PORT', '465'),
                'security' => env_value('WEBMAIL_SMTP_SECURITY', 'ssl'),
                'username' => env_value('WEBMAIL_SMTP_USER', 'help@mmhnu.online'),
                'password' => env_value('WEBMAIL_SMTP_PASS', ''),
            ],
        ],
    ],
];

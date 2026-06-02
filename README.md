# Premium PHP Webmail Dashboard

A responsive Gmail/Outlook-inspired webmail dashboard for cPanel IMAP mailboxes, built with PHP, IMAP, vanilla JavaScript, and PWA support.

## Quick start

1. Copy the sample environment file:
   ```bash
   cp .env.example .env
   ```
2. Set your IMAP/SMTP values for `help@mmhnu.online`.
3. Ensure PHP has the `imap` extension enabled.
4. Serve the project from a PHP-capable web server, or locally:
   ```bash
   php -S 127.0.0.1:8080
   ```
5. Open `http://127.0.0.1:8080`.

## Environment variables

The app reads credentials from environment variables first, then from `.env` when present. Do not commit real credentials.

```dotenv
WEBMAIL_DEFAULT_EMAIL=help@mmhnu.online
WEBMAIL_IMAP_HOST=mail.mmhnu.online
WEBMAIL_IMAP_PORT=993
WEBMAIL_IMAP_FLAGS=/imap/ssl/novalidate-cert
WEBMAIL_IMAP_USER=help@mmhnu.online
WEBMAIL_IMAP_PASS=change-me
WEBMAIL_SMTP_HOST=mail.mmhnu.online
WEBMAIL_SMTP_PORT=465
WEBMAIL_SMTP_SECURITY=ssl
WEBMAIL_SMTP_USER=help@mmhnu.online
WEBMAIL_SMTP_PASS=change-me
```

## Notes

- API endpoints are in `api.php` and return JSON.
- The UI shell is cacheable by the service worker for offline/PWA startup.
- IMAP calls are paginated and cached per request to avoid expensive mailbox scans.

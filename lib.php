<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

date_default_timezone_set($config['app']['timezone']);
session_name($config['security']['session_name']);
session_start();

function config(string $key)
{
    global $config;

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return null;
        }
        $value = $value[$segment];
    }

    return $value;
}

function db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        config('db.host'),
        config('db.port'),
        config('db.database'),
        config('db.charset')
    );

    $pdo = new PDO($dsn, (string) config('db.username'), (string) config('db.password'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    seedAdmin($pdo);

    return $pdo;
}

function seedAdmin(PDO $pdo): void
{
    static $seeded = false;
    if ($seeded) {
        return;
    }

    $seeded = true;
    $email = (string) config('security.admin_seed_email');
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);

    if ($stmt->fetch()) {
        return;
    }

    $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)');
    $insert->execute([
        'name' => 'Support Admin',
        'email' => $email,
        'password_hash' => password_hash((string) config('security.admin_seed_password'), PASSWORD_DEFAULT),
        'role' => 'admin',
    ]);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = compact('type', 'message');
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function currentUser(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function requireLogin(): array
{
    $user = currentUser();
    if (!$user) {
        flash('error', 'Please login to continue.');
        redirect('/login.php');
    }
    return $user;
}

function requireAdmin(): array
{
    $user = requireLogin();
    if ($user['role'] !== 'admin') {
        flash('error', 'Admin access only.');
        redirect('/dashboard.php');
    }
    return $user;
}

function generateTicketCode(): string
{
    return 'TKT-' . strtoupper(bin2hex(random_bytes(4)));
}

function sendEmailNotification(string $to, string $subject, string $body): array
{
    if (!config('email.enabled')) {
        return ['status' => 'queued', 'response' => 'Email disabled in config; notification queued only.'];
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: ' . config('email.from'),
    ];

    $sent = mail($to, $subject, $body, implode("\r\n", $headers));

    return [
        'status' => $sent ? 'sent' : 'failed',
        'response' => $sent ? 'mail() accepted the message.' : 'mail() failed to send the message.',
    ];
}

function sendWhatsAppNotification(string $phone, string $message): array
{
    if (!config('whatsapp.enabled')) {
        return ['status' => 'queued', 'response' => 'WhatsApp API disabled in config; notification queued only.'];
    }

    $payload = json_encode([
        'messaging_product' => 'whatsapp',
        'to' => preg_replace('/\D+/', '', $phone),
        'type' => 'text',
        'text' => ['body' => $message],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init((string) config('whatsapp.api_endpoint'));
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . config('whatsapp.access_token'),
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error !== '' || $statusCode >= 400) {
        return ['status' => 'failed', 'response' => $error !== '' ? $error : (string) $response];
    }

    return ['status' => 'sent', 'response' => (string) $response];
}

function queueNotification(int $ticketId, string $type, string $recipient, array $payload, string $status, string $response): void
{
    $stmt = db()->prepare('INSERT INTO notifications (ticket_id, type, recipient, payload, status, response_text) VALUES (:ticket_id, :type, :recipient, :payload, :status, :response_text)');
    $stmt->execute([
        'ticket_id' => $ticketId,
        'type' => $type,
        'recipient' => $recipient,
        'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        'status' => $status,
        'response_text' => $response,
    ]);
}

function createMessage(int $ticketId, int $senderId, string $message, string $channel = 'web'): void
{
    $stmt = db()->prepare('INSERT INTO messages (ticket_id, sender_id, message, channel) VALUES (:ticket_id, :sender_id, :message, :channel)');
    $stmt->execute([
        'ticket_id' => $ticketId,
        'sender_id' => $senderId,
        'message' => $message,
        'channel' => $channel,
    ]);

    $update = db()->prepare('UPDATE tickets SET last_message_at = NOW(), updated_at = NOW() WHERE id = :id');
    $update->execute(['id' => $ticketId]);
}

function ticketWithMessages(int $ticketId, array $user): ?array
{
    $sql = 'SELECT t.*, u.name, u.email, u.phone FROM tickets t INNER JOIN users u ON u.id = t.user_id WHERE t.id = :id';
    if ($user['role'] !== 'admin') {
        $sql .= ' AND t.user_id = :user_id';
    }

    $stmt = db()->prepare($sql);
    $params = ['id' => $ticketId];
    if ($user['role'] !== 'admin') {
        $params['user_id'] = $user['id'];
    }
    $stmt->execute($params);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        return null;
    }

    $msgStmt = db()->prepare('SELECT m.*, u.name, u.role FROM messages m INNER JOIN users u ON u.id = m.sender_id WHERE ticket_id = :ticket_id ORDER BY m.created_at ASC');
    $msgStmt->execute(['ticket_id' => $ticketId]);
    $ticket['messages'] = $msgStmt->fetchAll();

    return $ticket;
}

function renderHead(string $title, string $description, string $keywords = 'support system, ticket system, customer care, WhatsApp support, PHP support system'): void
{
    $fullTitle = $title . ' | ' . (string) config('app.name');
    $escapedTitle = e($fullTitle);
    $escapedDescription = e($description);
    $escapedKeywords = e($keywords);
    $author = e((string) config('app.name'));
    $baseUrl = rtrim((string) config('app.base_url'), '/');
    $currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
    $canonical = e($baseUrl . $currentPath);

    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$escapedTitle}</title>
    <meta name="description" content="{$escapedDescription}">
    <meta name="keywords" content="{$escapedKeywords}">
    <meta name="author" content="{$author}">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta name="theme-color" content="#4f46e5">
    <link rel="canonical" href="{$canonical}">
    <meta property="og:title" content="{$escapedTitle}">
    <meta property="og:description" content="{$escapedDescription}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{$canonical}">
    <meta property="og:site_name" content="{$author}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$escapedTitle}">
    <meta name="twitter:description" content="{$escapedDescription}">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Malayalam:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
HTML;
}

function renderFooter(): void
{
    $year = date('Y');
    $appName = e((string) config('app.name'));

    echo <<<HTML
<footer class="site-footer card" aria-label="Footer">
    <div>
        <strong>{$appName}</strong>
        <p class="muted">Premium customer support workspace with ticketing, admin replies, WhatsApp-style chat, and email-ready communication.</p>
    </div>
    <nav class="footer-links" aria-label="Footer links">
        <a href="/">Home</a>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/admin.php">Admin</a>
        <a href="/login.php">Login</a>
    </nav>
    <p class="footer-copy">© {$year} {$appName}. Crafted for fast, professional support experiences.</p>
</footer>
HTML;
}

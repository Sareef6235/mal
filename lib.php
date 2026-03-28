<?php

declare(strict_types=1);

session_start();

function config(string $key, mixed $default = null): mixed
{
    static $config = [
        'app.env' => 'development',
        'app.base_url' => 'http://localhost',
        'app.default_lang' => 'en',
        'db.dsn' => 'mysql:host=127.0.0.1;dbname=support_saas;charset=utf8mb4',
        'db.user' => 'root',
        'db.pass' => '',
        'security.csrf_ttl' => 7200,
        'support.duplicate_window_hours' => 24,
        'support.attachment_dir' => __DIR__ . '/storage/uploads',
        'support.allowed_mime' => [
            'image/png',
            'image/jpeg',
            'application/pdf',
            'text/plain',
        ],
        'support.max_upload_bytes' => 5 * 1024 * 1024,
        'email.admin_to' => 'support-admin@example.com',
        'email.from' => 'noreply@example.com',
        'email.smtp_host' => '',
        'email.smtp_user' => '',
        'email.smtp_pass' => '',
        'email.smtp_port' => 587,
        'whatsapp.phone' => '',
        'whatsapp.api_url' => 'https://graph.facebook.com/v22.0',
        'whatsapp.phone_number_id' => '',
        'whatsapp.token' => '',
    ];

    return $config[$key] ?? $default;
}

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO(
        (string) config('db.dsn'),
        (string) config('db.user'),
        (string) config('db.pass'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): array
{
    $user = currentUser();
    if (!$user) {
        flash('error', __('auth.login_required'));
        redirect('/login.php');
    }

    return $user;
}

function requireAdmin(): array
{
    $user = requireLogin();
    if (($user['role'] ?? 'user') !== 'admin' && ($user['role'] ?? '') !== 'agent') {
        http_response_code(403);
        exit('Forbidden');
    }

    return $user;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $flash;
}

function setLanguage(string $lang): void
{
    $_SESSION['lang'] = in_array($lang, ['en', 'ml'], true) ? $lang : config('app.default_lang');
}

function getLanguage(): string
{
    return $_SESSION['lang'] ?? (string) config('app.default_lang', 'en');
}

function __(string $key, array $replace = []): string
{
    static $messages = [];

    $lang = getLanguage();
    if (!isset($messages[$lang])) {
        $file = __DIR__ . '/lang/' . $lang . '.php';
        $messages[$lang] = file_exists($file) ? require $file : [];
    }

    $line = $messages[$lang][$key] ?? $key;
    foreach ($replace as $k => $v) {
        $line = str_replace(':' . $k, (string) $v, $line);
    }

    return $line;
}

function generateCsrfToken(string $form): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['_csrf'][$form] = ['token' => $token, 'time' => time()];
    return $token;
}

function validateCsrfToken(string $form, string $token): bool
{
    $record = $_SESSION['_csrf'][$form] ?? null;
    if (!$record) {
        return false;
    }

    $ttl = (int) config('security.csrf_ttl', 7200);
    $valid = hash_equals($record['token'], $token) && (time() - (int) $record['time'] <= $ttl);
    unset($_SESSION['_csrf'][$form]);
    return $valid;
}

function generateTicketCode(): string
{
    return 'TKT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

function priorityDueDate(string $priority): DateTimeImmutable
{
    $hours = match (strtolower($priority)) {
        'urgent' => 4,
        'high' => 8,
        'normal' => 24,
        'low' => 72,
        default => 24,
    };

    return (new DateTimeImmutable('now'))->modify('+' . $hours . ' hours');
}

function isDuplicateTicket(int $userId, string $subject, string $message): bool
{
    $hours = (int) config('support.duplicate_window_hours', 24);
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM tickets t
         INNER JOIN ticket_messages m ON m.ticket_id = t.id
         WHERE t.user_id = :user_id
           AND t.subject = :subject
           AND m.message = :message
           AND t.created_at >= (NOW() - INTERVAL :hours HOUR)'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':subject', $subject);
    $stmt->bindValue(':message', $message);
    $stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
}

function createMessage(int $ticketId, int $userId, string $message, string $channel = 'web', string $type = 'reply', bool $isInternal = false): int
{
    $stmt = db()->prepare(
        'INSERT INTO ticket_messages (ticket_id, user_id, message_type, message, channel, is_internal)
         VALUES (:ticket_id, :user_id, :message_type, :message, :channel, :is_internal)'
    );
    $stmt->execute([
        'ticket_id' => $ticketId,
        'user_id' => $userId,
        'message_type' => $type,
        'message' => $message,
        'channel' => $channel,
        'is_internal' => $isInternal ? 1 : 0,
    ]);

    return (int) db()->lastInsertId();
}

function saveTicketTags(int $ticketId, array $tags): void
{
    if (!$tags) {
        return;
    }

    $select = db()->prepare('SELECT id FROM tags WHERE name = :name LIMIT 1');
    $insertTag = db()->prepare('INSERT INTO tags (name) VALUES (:name)');
    $insertPivot = db()->prepare('INSERT IGNORE INTO ticket_tags (ticket_id, tag_id) VALUES (:ticket_id, :tag_id)');

    foreach ($tags as $tag) {
        $name = trim(mb_strtolower($tag));
        if ($name === '') {
            continue;
        }

        $select->execute(['name' => $name]);
        $tagId = (int) ($select->fetchColumn() ?: 0);

        if ($tagId === 0) {
            $insertTag->execute(['name' => $name]);
            $tagId = (int) db()->lastInsertId();
        }

        $insertPivot->execute(['ticket_id' => $ticketId, 'tag_id' => $tagId]);
    }
}

function storeAttachments(int $ticketId, array $files): void
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return;
    }

    $dir = (string) config('support.attachment_dir');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $allowedMime = config('support.allowed_mime', []);
    $maxBytes = (int) config('support.max_upload_bytes', 5 * 1024 * 1024);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $stmt = db()->prepare(
        'INSERT INTO ticket_attachments (ticket_id, original_name, stored_name, mime_type, size_bytes)
         VALUES (:ticket_id, :original_name, :stored_name, :mime_type, :size_bytes)'
    );

    foreach ($files['name'] as $idx => $originalName) {
        if (($files['error'][$idx] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmp = $files['tmp_name'][$idx] ?? '';
        $size = (int) ($files['size'][$idx] ?? 0);
        if ($size <= 0 || $size > $maxBytes || !is_uploaded_file($tmp)) {
            continue;
        }

        $mime = finfo_file($finfo, $tmp) ?: 'application/octet-stream';
        if (!in_array($mime, $allowedMime, true)) {
            continue;
        }

        $ext = pathinfo((string) $originalName, PATHINFO_EXTENSION);
        $stored = $ticketId . '_' . bin2hex(random_bytes(8)) . ($ext ? '.' . strtolower($ext) : '');
        $target = $dir . '/' . $stored;

        if (!move_uploaded_file($tmp, $target)) {
            continue;
        }

        $stmt->execute([
            'ticket_id' => $ticketId,
            'original_name' => mb_substr((string) $originalName, 0, 255),
            'stored_name' => $stored,
            'mime_type' => $mime,
            'size_bytes' => $size,
        ]);
    }

    finfo_close($finfo);
}

function queueNotification(int $ticketId, string $channel, string $recipient, array $payload, string $status, string $response = ''): void
{
    $stmt = db()->prepare(
        'INSERT INTO notification_logs (ticket_id, channel, recipient, payload_json, status, response)
         VALUES (:ticket_id, :channel, :recipient, :payload_json, :status, :response)'
    );
    $stmt->execute([
        'ticket_id' => $ticketId,
        'channel' => $channel,
        'recipient' => $recipient,
        'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        'status' => $status,
        'response' => $response,
    ]);
}

function sendEmailNotification(string $to, string $subject, string $body): array
{
    if ($to === '') {
        return ['status' => 'skipped', 'response' => 'Missing recipient'];
    }

    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = (string) config('email.smtp_host');
            $mail->Port = (int) config('email.smtp_port', 587);
            $mail->SMTPAuth = true;
            $mail->Username = (string) config('email.smtp_user');
            $mail->Password = (string) config('email.smtp_pass');
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->setFrom((string) config('email.from'), 'Support Desk');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
            return ['status' => 'sent', 'response' => 'PHPMailer SMTP'];
        } catch (Throwable $e) {
            return ['status' => 'failed', 'response' => $e->getMessage()];
        }
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . config('email.from'),
    ];

    $ok = @mail($to, $subject, $body, implode("\r\n", $headers));
    return [
        'status' => $ok ? 'sent' : 'failed',
        'response' => $ok ? 'mail() accepted' : 'mail() failed',
    ];
}

function sendWhatsAppNotification(string $phone, string $message): array
{
    $token = (string) config('whatsapp.token');
    $phoneNumberId = (string) config('whatsapp.phone_number_id');
    $apiBase = rtrim((string) config('whatsapp.api_url'), '/');

    if ($token === '' || $phoneNumberId === '') {
        return ['status' => 'skipped', 'response' => 'WhatsApp credentials missing'];
    }

    $url = $apiBase . '/' . $phoneNumberId . '/messages';
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type' => 'text',
        'text' => ['body' => $message],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err !== '') {
        return ['status' => 'failed', 'response' => $err];
    }

    return [
        'status' => ($httpCode >= 200 && $httpCode < 300) ? 'sent' : 'failed',
        'response' => (string) $response,
    ];
}

function sendSmsNotification(string $phone, string $message): array
{
    return ['status' => 'queued', 'response' => 'Integrate provider here', 'phone' => $phone, 'message' => $message];
}

function logAudit(?int $ticketId, int $actorId, string $action, array $meta = []): void
{
    $stmt = db()->prepare(
        'INSERT INTO audit_logs (ticket_id, actor_id, action, meta_json)
         VALUES (:ticket_id, :actor_id, :action, :meta_json)'
    );
    $stmt->execute([
        'ticket_id' => $ticketId,
        'actor_id' => $actorId,
        'action' => $action,
        'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE),
    ]);
}

function createTicket(array $user, array $input, array $files): int
{
    $subject = trim((string) ($input['subject'] ?? ''));
    $message = trim((string) ($input['message'] ?? ''));
    $orderReference = trim((string) ($input['order_reference'] ?? ''));
    $category = trim((string) ($input['category'] ?? 'General Support'));
    $priority = trim((string) ($input['priority'] ?? 'Normal'));
    $tags = array_filter(array_map('trim', explode(',', (string) ($input['tags'] ?? ''))));

    if ($subject === '' || $message === '') {
        throw new InvalidArgumentException(__('ticket.required_subject_message'));
    }

    if (isDuplicateTicket((int) $user['id'], $subject, $message)) {
        throw new RuntimeException(__('ticket.duplicate_detected'));
    }

    $dueAt = priorityDueDate($priority)->format('Y-m-d H:i:s');
    $ticketCode = generateTicketCode();

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO tickets (ticket_code, user_id, subject, order_reference, category, priority, status, due_at)
             VALUES (:ticket_code, :user_id, :subject, :order_reference, :category, :priority, :status, :due_at)'
        );
        $stmt->execute([
            'ticket_code' => $ticketCode,
            'user_id' => (int) $user['id'],
            'subject' => $subject,
            'order_reference' => $orderReference !== '' ? $orderReference : null,
            'category' => $category,
            'priority' => $priority,
            'status' => 'Open',
            'due_at' => $dueAt,
        ]);

        $ticketId = (int) $pdo->lastInsertId();
        createMessage($ticketId, (int) $user['id'], $message, 'web', 'reply', false);
        saveTicketTags($ticketId, $tags);
        storeAttachments($ticketId, $files['attachments'] ?? []);
        logAudit($ticketId, (int) $user['id'], 'ticket_created', ['priority' => $priority, 'tags' => $tags]);

        $adminEmail = (string) (config('email.admin_to') ?? '');
        if ($adminEmail !== '') {
            $emailRes = sendEmailNotification($adminEmail, 'New Ticket: ' . $subject, $message);
            queueNotification($ticketId, 'email', $adminEmail, ['subject' => $subject, 'message' => $message], $emailRes['status'], (string) ($emailRes['response'] ?? ''));
        }

        $targetPhone = (string) (($user['phone'] ?? '') ?: config('whatsapp.phone', ''));
        if ($targetPhone !== '') {
            $waRes = sendWhatsAppNotification($targetPhone, 'Ticket ' . $ticketCode . ' created: ' . $subject);
            queueNotification($ticketId, 'whatsapp', $targetPhone, ['subject' => $subject], $waRes['status'], (string) ($waRes['response'] ?? ''));
        }

        $pdo->commit();
        return $ticketId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function updateTicketStatus(int $ticketId, string $status, int $actorId): void
{
    $allowed = ['Open', 'In Progress', 'Waiting', 'Resolved', 'Closed'];
    if (!in_array($status, $allowed, true)) {
        throw new InvalidArgumentException('Invalid status');
    }

    $stmt = db()->prepare('UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $ticketId]);
    logAudit($ticketId, $actorId, 'ticket_status_changed', ['status' => $status]);
}

function assignTicket(int $ticketId, int $agentId, int $actorId): void
{
    $stmt = db()->prepare('UPDATE tickets SET assigned_to = :assigned_to, updated_at = NOW() WHERE id = :id');
    $stmt->execute(['assigned_to' => $agentId, 'id' => $ticketId]);
    logAudit($ticketId, $actorId, 'ticket_assigned', ['assigned_to' => $agentId]);
}

function renderHead(string $title, string $description = ''): void
{
    echo '<!doctype html><html lang="' . e(getLanguage()) . '"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="description" content="' . e($description) . '">';
    echo '<title>' . e($title) . '</title></head>';
}

function renderFooter(): void
{
    echo '<footer style="text-align:center;padding:20px;color:#94a3b8">© ' . date('Y') . ' Support SaaS</footer></html>';
}

<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/lib.php';

const MAX_IMAGE_BYTES = 200 * 1024;
const MAX_VIDEO_BYTES = 5 * 1024 * 1024;
const MAX_AUDIO_BYTES = 200 * 1024;
const ATTACHMENT_TOKEN_PATTERN = '/\[\[attachment:(.*?)\]\]/';
const TICKET_STATUSES = ['Open', 'Pending', 'Resolved', 'Closed'];

if (!function_exists('ensureTicketUploadDirectory')) {
    function ensureTicketUploadDirectory(int $ticketId): string
    {
        $directory = __DIR__ . '/uploads/tickets/' . $ticketId;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        return $directory;
    }
}

if (!function_exists('publicUploadPath')) {
    function publicUploadPath(int $ticketId, string $basename): string
    {
        return '/qwe1/uploads/tickets/' . $ticketId . '/' . rawurlencode($basename);
    }
}

if (!function_exists('friendlyAttachmentType')) {
    function friendlyAttachmentType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'file';
    }
}

if (!function_exists('sanitizeUploadName')) {
    function sanitizeUploadName(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name) ?: 'attachment';

        return trim($safeName, '-') . '-' . bin2hex(random_bytes(4)) . ($extension !== '' ? '.' . $extension : '');
    }
}

if (!function_exists('compressImageToLimit')) {
    function compressImageToLimit(string $sourcePath, string $destinationPath, int $targetBytes): array
    {
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            throw new RuntimeException('Invalid image file.');
        }

        [$width, $height, $imageType] = $imageInfo;
        $createMap = [
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_WEBP => 'imagecreatefromwebp',
        ];

        if (!isset($createMap[$imageType]) || !function_exists($createMap[$imageType])) {
            throw new RuntimeException('Unsupported image format. Please upload JPG, PNG, or WEBP.');
        }

        $sourceImage = $createMap[$imageType]($sourcePath);
        if (!$sourceImage) {
            throw new RuntimeException('Unable to process image.');
        }

        $currentWidth = $width;
        $currentHeight = $height;
        $workingImage = $sourceImage;
        $quality = 82;

        for ($attempt = 0; $attempt < 6; $attempt++) {
            imagejpeg($workingImage, $destinationPath, $quality);
            clearstatcache(true, $destinationPath);
            if (filesize($destinationPath) <= $targetBytes) {
                if ($workingImage !== $sourceImage) {
                    imagedestroy($workingImage);
                }
                imagedestroy($sourceImage);

                return ['path' => $destinationPath, 'mime' => 'image/jpeg', 'size' => filesize($destinationPath)];
            }

            $quality = max(45, $quality - 10);
            $currentWidth = (int) max(480, floor($currentWidth * 0.82));
            $currentHeight = (int) max(480, floor($currentHeight * 0.82));
            $resized = imagecreatetruecolor($currentWidth, $currentHeight);
            imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, $currentWidth, $currentHeight, $width, $height);
            if ($workingImage !== $sourceImage) {
                imagedestroy($workingImage);
            }
            $workingImage = $resized;
        }

        if ($workingImage !== $sourceImage) {
            imagedestroy($workingImage);
        }
        imagedestroy($sourceImage);

        clearstatcache(true, $destinationPath);
        if (file_exists($destinationPath) && filesize($destinationPath) <= $targetBytes) {
            return ['path' => $destinationPath, 'mime' => 'image/jpeg', 'size' => filesize($destinationPath)];
        }

        throw new RuntimeException('Unable to reduce image below 200 KB.');
    }
}

if (!function_exists('compressMediaWithFfmpeg')) {
    function compressMediaWithFfmpeg(string $sourcePath, string $destinationPath, string $mode, int $targetBytes): array
    {
        $ffmpeg = trim((string) shell_exec('command -v ffmpeg'));
        if ($ffmpeg === '') {
            throw new RuntimeException('FFmpeg is required on the server for audio/video optimization.');
        }

        $durationOutput = shell_exec($ffmpeg . ' -i ' . escapeshellarg($sourcePath) . ' 2>&1');
        preg_match('/Duration: (\d+):(\d+):(\d+(?:\.\d+)?)/', (string) $durationOutput, $matches);
        $durationSeconds = 30.0;
        if ($matches) {
            $durationSeconds = ((int) $matches[1] * 3600) + ((int) $matches[2] * 60) + (float) $matches[3];
        }
        $durationSeconds = max(1.0, $durationSeconds);
        $targetBitrate = (int) max(32000, floor(($targetBytes * 8) / $durationSeconds * 0.92));

        if ($mode === 'audio') {
            $command = sprintf(
                '%s -y -i %s -vn -c:a libmp3lame -b:a %dk %s 2>&1',
                escapeshellcmd($ffmpeg),
                escapeshellarg($sourcePath),
                max(32, (int) floor($targetBitrate / 1000)),
                escapeshellarg($destinationPath)
            );
            $mime = 'audio/mpeg';
        } else {
            $videoBitrate = max(250, (int) floor(($targetBitrate - 96000) / 1000));
            $command = sprintf(
                "%s -y -i %s -vf 'scale=min(1280,iw):-2' -c:v libx264 -preset veryfast -b:v %dk -maxrate %dk -bufsize %dk -c:a aac -b:a 96k %s 2>&1",
                escapeshellcmd($ffmpeg),
                escapeshellarg($sourcePath),
                $videoBitrate,
                max(300, $videoBitrate),
                max(600, $videoBitrate * 2),
                escapeshellarg($destinationPath)
            );
            $mime = 'video/mp4';
        }

        shell_exec($command);
        clearstatcache(true, $destinationPath);
        if (!file_exists($destinationPath) || filesize($destinationPath) > $targetBytes) {
            throw new RuntimeException('Unable to optimize uploaded media to the required size.');
        }

        return ['path' => $destinationPath, 'mime' => $mime, 'size' => filesize($destinationPath)];
    }
}

if (!function_exists('processUploadedAttachment')) {
    function processUploadedAttachment(array $file, int $ticketId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Attachment upload failed.');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? 'attachment');
        $mimeType = mime_content_type($tmpPath) ?: 'application/octet-stream';
        $group = friendlyAttachmentType($mimeType);
        if (!in_array($group, ['image', 'audio', 'video'], true)) {
            throw new RuntimeException('Please upload an image, audio file, or video file only.');
        }

        $directory = ensureTicketUploadDirectory($ticketId);
        $safeName = sanitizeUploadName($originalName);
        $destinationPath = $directory . '/' . $safeName;

        if ($group === 'image') {
            $jpegPath = preg_replace('/\.[^.]+$/', '', $destinationPath) . '.jpg';
            $result = compressImageToLimit($tmpPath, $jpegPath, MAX_IMAGE_BYTES);
            $safeName = basename($jpegPath);
        } elseif ($group === 'audio') {
            $mp3Path = preg_replace('/\.[^.]+$/', '', $destinationPath) . '.mp3';
            $result = compressMediaWithFfmpeg($tmpPath, $mp3Path, 'audio', MAX_AUDIO_BYTES);
            $safeName = basename($mp3Path);
        } else {
            $mp4Path = preg_replace('/\.[^.]+$/', '', $destinationPath) . '.mp4';
            $result = compressMediaWithFfmpeg($tmpPath, $mp4Path, 'video', MAX_VIDEO_BYTES);
            $safeName = basename($mp4Path);
        }

        return [
            'name' => $originalName,
            'stored_name' => $safeName,
            'path' => publicUploadPath($ticketId, $safeName),
            'mime' => $result['mime'],
            'type' => $group,
            'size' => $result['size'],
        ];
    }
}

if (!function_exists('extractAttachmentTokens')) {
    function extractAttachmentTokens(string $message): array
    {
        preg_match_all(ATTACHMENT_TOKEN_PATTERN, $message, $matches);
        $attachments = [];
        foreach ($matches[1] ?? [] as $token) {
            $decoded = json_decode(base64_decode($token, true) ?: '', true);
            if (is_array($decoded) && isset($decoded['path'], $decoded['type'])) {
                $attachments[] = $decoded;
            }
        }

        $cleanMessage = trim((string) preg_replace(ATTACHMENT_TOKEN_PATTERN, '', $message));

        return [$cleanMessage, $attachments];
    }
}

if (!function_exists('appendAttachmentTokens')) {
    function appendAttachmentTokens(string $message, array $attachments): string
    {
        $tokens = [];
        foreach ($attachments as $attachment) {
            $tokens[] = '[[attachment:' . base64_encode((string) json_encode($attachment, JSON_UNESCAPED_SLASHES)) . ']]';
        }

        return trim($message) . (empty($tokens) ? '' : "\n\n" . implode("\n", $tokens));
    }
}

if (!function_exists('normalizeTicketStatus')) {
    function normalizeTicketStatus(string $status): string
    {
        return in_array($status, TICKET_STATUSES, true) ? $status : 'Open';
    }
}

if (!function_exists('messageBelongsToUser')) {
    function messageBelongsToUser(array $message, array $user): bool
    {
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }

        return (int) ($message['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
    }
}

if (!function_exists('findTicketMessage')) {
    function findTicketMessage(array $messages, int $messageId): ?array
    {
        foreach ($messages as $message) {
            if ((int) ($message['id'] ?? 0) === $messageId) {
                return $message;
            }
        }

        return null;
    }
}

if (!function_exists('updateTicketMessageBody')) {
    function updateTicketMessageBody(int $messageId, int $ticketId, string $message): void
    {
        $statement = db()->prepare('UPDATE messages SET message = :message WHERE id = :id AND ticket_id = :ticket_id');
        $statement->execute([
            'message' => $message,
            'id' => $messageId,
            'ticket_id' => $ticketId,
        ]);
    }
}

if (!function_exists('deleteTicketMessageRow')) {
    function deleteTicketMessageRow(int $messageId, int $ticketId): void
    {
        $statement = db()->prepare('DELETE FROM messages WHERE id = :id AND ticket_id = :ticket_id');
        $statement->execute([
            'id' => $messageId,
            'ticket_id' => $ticketId,
        ]);
    }
}

if (!function_exists('clearTicketNotifications')) {
    function clearTicketNotifications(int $ticketId): void
    {
        $statement = db()->prepare('DELETE FROM notifications WHERE ticket_id = :ticket_id');
        $statement->execute(['ticket_id' => $ticketId]);
    }
}

if (!function_exists('notificationPayloadArray')) {
    function notificationPayloadArray(array $notification): array
    {
        $payload = $notification['payload'] ?? null;
        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return is_array($payload) ? $payload : [];
    }
}

if (!function_exists('notificationPreview')) {
    function notificationPreview(array $notification): string
    {
        $payload = notificationPayloadArray($notification);
        if ($payload !== []) {
            $message = trim((string) ($payload['message'] ?? ''));
            if ($message !== '') {
                return $message;
            }

            $attachments = $payload['attachments'] ?? [];
            if (is_array($attachments) && $attachments !== []) {
                return 'Attachment shared: ' . implode(', ', array_map('strval', $attachments));
            }
        }

        $message = trim((string) ($notification['message'] ?? ''));
        if ($message !== '') {
            return $message;
        }

        $response = trim((string) ($notification['response_text'] ?? ''));
        if ($response !== '') {
            return $response;
        }

        return 'No message';
    }
}

if (!function_exists('notificationMessageId')) {
    function notificationMessageId(array $notification): int
    {
        $payload = notificationPayloadArray($notification);
        return (int) ($payload['message_id'] ?? 0);
    }
}

if (!function_exists('messageDisplayName')) {
    function messageDisplayName(array $message, array $ticket, array $currentUser): string
    {
        foreach (['name', 'user_name', 'sender_name', 'full_name', 'display_name', 'username'] as $field) {
            $value = trim((string) ($message[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $email = trim((string) ($message['email'] ?? ''));
        if ($email !== '') {
            return $email;
        }

        $role = (string) ($message['role'] ?? '');
        if ($role === 'admin') {
            $adminName = trim((string) ($currentUser['name'] ?? 'Admin'));
            return $adminName !== '' ? $adminName : 'Admin';
        }

        $ticketName = trim((string) ($ticket['name'] ?? ''));
        if ($ticketName !== '') {
            return $ticketName;
        }

        return 'Customer';
    }
}

if (!function_exists('renderNotificationList')) {
    function renderNotificationList(array $notificationRows): string
    {
        ob_start();
        if ($notificationRows === []) {
            echo '<p class="muted">No notifications logged yet.</p>';
        } else {
            foreach ($notificationRows as $row) {
                $notificationMessage = notificationPreview((array) $row);
                $messageId = notificationMessageId((array) $row);
                $link = $messageId > 0 ? '#message-' . $messageId : '';
                echo '<article class="notify-item">';
                echo '<strong>' . e(strtoupper((string) $row['type'])) . '</strong>';
                echo '<span>' . e((string) $row['status']) . '</span>';
                if ($link !== '') {
                    echo '<a class="notify-link" href="' . e($link) . '">' . e($notificationMessage) . '</a>';
                } else {
                    echo '<small>' . e($notificationMessage) . '</small>';
                }
                echo '</article>';
            }
        }

        return (string) ob_get_clean();
    }
}

if (!function_exists('renderMessageStream')) {
    function renderMessageStream(array $ticket, array $user, int $editingMessageId, int $ticketId): string
    {
        ob_start();
        foreach (($ticket['messages'] ?? []) as $message) {
            [$messageText, $messageAttachments] = extractAttachmentTokens((string) ($message['message'] ?? ''));
            $isAdmin = (($message['role'] ?? '') === 'admin');
            $canManageMessage = messageBelongsToUser($message, $user);
            $isEditing = $editingMessageId === (int) ($message['id'] ?? 0) && $canManageMessage;
            $senderName = messageDisplayName((array) $message, $ticket, $user);
            ?>
            <article class="msg <?= $isAdmin ? 'agent' : 'customer' ?>" id="message-<?= (int) ($message['id'] ?? 0) ?>">
                <div class="sender-pill <?= $isAdmin ? 'admin' : 'customer' ?>">
                    <?= $isAdmin ? 'Admin' : 'Customer' ?>
                </div>
                <div class="msg-head">
                    <strong><?= e($senderName) ?></strong>
                    <span><?= e($message['created_at'] ?? '') ?></span>
                </div>

                <?php if ($isEditing): ?>
                    <form method="post" class="message-edit-form" aria-label="Edit message">
                        <input type="hidden" name="action" value="edit_message">
                        <input type="hidden" name="message_id" value="<?= (int) ($message['id'] ?? 0) ?>">
                        <label class="sr-only" for="edit-message-<?= (int) ($message['id'] ?? 0) ?>">Edit message</label>
                        <textarea id="edit-message-<?= (int) ($message['id'] ?? 0) ?>" name="message" rows="5"><?= e($messageText) ?></textarea>
                        <div class="msg-actions">
                            <button class="secondary-btn" type="submit">Save</button>
                            <a class="message-action" href="/qwe1/ticket.php?id=<?= $ticketId ?>#message-<?= (int) ($message['id'] ?? 0) ?>">Cancel</a>
                        </div>
                    </form>
                <?php else: ?>
                    <?php if ($messageText !== ''): ?>
                        <p class="msg-body"><?= nl2br(e($messageText)) ?></p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($messageAttachments !== []): ?>
                    <div class="attachment-grid">
                        <?php foreach ($messageAttachments as $attachment): ?>
                            <div class="attachment-card">
                                <strong><?= e($attachment['name'] ?? 'Attachment') ?></strong>
                                <?php if (($attachment['type'] ?? '') === 'image'): ?>
                                    <img src="<?= e($attachment['path']) ?>" alt="<?= e($attachment['name'] ?? 'Uploaded image') ?>">
                                <?php elseif (($attachment['type'] ?? '') === 'video'): ?>
                                    <video controls preload="metadata">
                                        <source src="<?= e($attachment['path']) ?>" type="<?= e($attachment['mime'] ?? 'video/mp4') ?>">
                                    </video>
                                <?php elseif (($attachment['type'] ?? '') === 'audio'): ?>
                                    <audio controls preload="metadata">
                                        <source src="<?= e($attachment['path']) ?>" type="<?= e($attachment['mime'] ?? 'audio/mpeg') ?>">
                                    </audio>
                                <?php endif; ?>
                                <div class="attachment-meta">
                                    <?= e(strtoupper((string) ($attachment['type'] ?? 'file'))) ?> · <?= e((string) round(((int) ($attachment['size'] ?? 0)) / 1024, 1)) ?> KB
                                </div>
                                <a href="<?= e($attachment['path']) ?>" target="_blank" rel="noopener">Open attachment</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($canManageMessage): ?>
                    <div class="msg-actions">
                        <a class="message-action" href="/qwe1/ticket.php?id=<?= $ticketId ?>&edit=<?= (int) ($message['id'] ?? 0) ?>#message-<?= (int) ($message['id'] ?? 0) ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this message?');">
                            <input type="hidden" name="action" value="delete_message">
                            <input type="hidden" name="message_id" value="<?= (int) ($message['id'] ?? 0) ?>">
                            <button class="message-action delete" type="submit">Delete</button>
                        </form>
                    </div>
                <?php endif; ?>

                <small><?= e($message['channel'] ?? '') ?></small>
            </article>
            <?php
        }

        return (string) ob_get_clean();
    }
}

$user = requireLogin();
$ticketId = (int) ($_GET['id'] ?? 0);
$ticket = ticketWithMessages($ticketId, $user);
if (!$ticket) {
    flash('error', 'Ticket not found.');
    redirect($user['role'] === 'admin' ? '/qwe1/admin.php' : '/qwe1/dashboard.php');
}

$editingMessageId = max(0, (int) ($_GET['edit'] ?? 0));

if (($_GET['partial'] ?? '') === 'stream') {
    $notifications = db()->prepare('SELECT * FROM notifications WHERE ticket_id = :ticket_id ORDER BY created_at DESC LIMIT 8');
    $notifications->execute(['ticket_id' => $ticketId]);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'messages_html' => renderMessageStream($ticket, $user, 0, $ticketId),
        'notifications_html' => renderNotificationList($notifications->fetchAll()),
        'message_count' => count((array) ($ticket['messages'] ?? [])),
        'last_message_id' => (int) (($ticket['messages'][array_key_last($ticket['messages'])]['id'] ?? 0)),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'reply');
    $message = trim((string) ($_POST['message'] ?? ''));

    try {
        if ($action === 'update_status') {
            if ($user['role'] !== 'admin') {
                throw new RuntimeException('Only admins can update ticket status.');
            }

            $status = normalizeTicketStatus((string) ($_POST['status'] ?? 'Open'));
            $update = db()->prepare('UPDATE tickets SET status = :status WHERE id = :id');
            $update->execute(['status' => $status, 'id' => $ticketId]);
            flash('success', 'Ticket status updated successfully.');
            redirect('/qwe1/ticket.php?id=' . $ticketId);
        }

        if ($action === 'clear_notifications') {
            clearTicketNotifications($ticketId);
            flash('success', 'Notifications cleared successfully.');
            redirect('/qwe1/ticket.php?id=' . $ticketId);
        }

        if ($action === 'edit_message') {
            $messageId = (int) ($_POST['message_id'] ?? 0);
            $targetMessage = findTicketMessage((array) ($ticket['messages'] ?? []), $messageId);
            if (!$targetMessage || !messageBelongsToUser($targetMessage, $user)) {
                throw new RuntimeException('You cannot edit this message.');
            }

            [, $attachments] = extractAttachmentTokens((string) ($targetMessage['message'] ?? ''));
            if ($message === '' && $attachments === []) {
                throw new RuntimeException('Message cannot be empty.');
            }

            updateTicketMessageBody($messageId, $ticketId, appendAttachmentTokens($message, $attachments));
            flash('success', 'Message updated successfully.');
            redirect('/qwe1/ticket.php?id=' . $ticketId . '#message-' . $messageId);
        }

        if ($action === 'delete_message') {
            $messageId = (int) ($_POST['message_id'] ?? 0);
            $targetMessage = findTicketMessage((array) ($ticket['messages'] ?? []), $messageId);
            if (!$targetMessage || !messageBelongsToUser($targetMessage, $user)) {
                throw new RuntimeException('You cannot delete this message.');
            }

            deleteTicketMessageRow($messageId, $ticketId);
            flash('success', 'Message deleted successfully.');
            redirect('/qwe1/ticket.php?id=' . $ticketId);
        }

        $attachments = [];
        if (!empty($_FILES['attachments']['name']) && is_array($_FILES['attachments']['name'])) {
            foreach ($_FILES['attachments']['name'] as $index => $name) {
                if ($name === '') {
                    continue;
                }

                $file = [
                    'name' => $_FILES['attachments']['name'][$index],
                    'type' => $_FILES['attachments']['type'][$index],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$index],
                    'error' => $_FILES['attachments']['error'][$index],
                    'size' => $_FILES['attachments']['size'][$index],
                ];
                $attachments[] = processUploadedAttachment($file, $ticketId);
            }
        }

        if ($message !== '' || $attachments !== []) {
            $storedMessage = appendAttachmentTokens($message !== '' ? $message : 'Attachment shared.', $attachments);
            createMessage($ticketId, (int) $user['id'], $storedMessage, (($user['role'] ?? '') === 'admin') ? 'admin' : 'web');
            $refreshedTicket = ticketWithMessages($ticketId, $user) ?: $ticket;
            $latestMessage = (array) (end($refreshedTicket['messages']) ?: []);
            $latestMessageId = (int) ($latestMessage['id'] ?? 0);
            $notificationPayload = [
                'message' => $message,
                'attachments' => array_column($attachments, 'stored_name'),
                'message_id' => $latestMessageId,
            ];
            $email = $ticket['email'] ?? null;

            if (!empty($email) && is_string($email)) {
                $emailResult = sendEmailNotification(
                    $email,
                    'Ticket update: ' . ($ticket['subject'] ?? ''),
                    $message !== '' ? $message : 'New attachment shared in your ticket.'
                );

                queueNotification(
                    $ticketId,
                    'email',
                    $email,
                    $notificationPayload,
                    $emailResult['status'],
                    $emailResult['response']
                );
            }

            $phone = $ticket['phone'] ?? config('whatsapp.phone');
            if (!empty($phone)) {
                $waResult = sendWhatsAppNotification($phone, $message !== '' ? $message : 'New attachment shared in your ticket.');
                queueNotification(
                    $ticketId,
                    'whatsapp',
                    $phone,
                    $notificationPayload,
                    $waResult['status'],
                    $waResult['response']
                );
            }

            if ($user['role'] === 'admin' && isset($_POST['status'])) {
                $update = db()->prepare('UPDATE tickets SET status = :status WHERE id = :id');
                $update->execute(['status' => normalizeTicketStatus((string) $_POST['status']), 'id' => $ticketId]);
            }

            flash('success', 'Reply sent successfully.');
        } else {
            flash('error', 'Please enter a message or upload at least one attachment.');
        }
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect('/qwe1/ticket.php?id=' . $ticketId);
}

$ticket = ticketWithMessages($ticketId, $user) ?: $ticket;
$flash = getFlash();
$notifications = db()->prepare('SELECT * FROM notifications WHERE ticket_id = :ticket_id ORDER BY created_at DESC LIMIT 8');
$notifications->execute(['ticket_id' => $ticketId]);
$notificationRows = $notifications->fetchAll();
renderHead(e($ticket['subject']), 'Professional support ticket conversation with sender labels, media uploads, live updates, and optimized notifications.');
?>
<body>
<div class="page-shell app-page">
    <header class="topbar glass" aria-label="Ticket header">
        <div class="brand">
            <div class="brand-mark"><?= $user['role'] === 'admin' ? 'AD' : 'PS' ?></div>
            <div>
                <strong><?= $user['role'] === 'admin' ? 'Admin Ticket View' : 'Customer Ticket View' ?></strong>
                <p><?= e($ticket['ticket_code']) ?> · <?= e($ticket['subject']) ?></p>
            </div>
        </div>
        <nav class="menu" aria-label="Ticket navigation">
            <a href="<?= $user['role'] === 'admin' ? '/qwe1/admin.php' : '/qwe1/dashboard.php' ?>">Back</a>
            <a href="/qwe1/logout.php">Logout</a>
        </nav>
    </header>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; }
        .app-page { max-width: 1300px; margin: auto; padding: 20px; }
        .glass, .card { background: rgba(255,255,255,0.05); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; }
        .topbar, .chat-header, .msg-head, .composer-tools, .meta-list div, .sidebar-header { display: flex; justify-content: space-between; align-items: center; }
        .topbar { padding: 15px 20px; }
        .brand { display: flex; gap: 12px; align-items: center; }
        .brand-mark { background: linear-gradient(135deg,#2563eb,#3b82f6); padding: 10px; border-radius: 10px; font-weight: 700; }
        .menu a, .footer-grid a { color: #cbd5f5; text-decoration: none; }
        .menu a { margin-left: 15px; }
        .chat-layout { display: grid; grid-template-columns: 320px 1fr; gap: 20px; margin-top: 20px; }
        @media (max-width: 900px) {
            .chat-layout { grid-template-columns: 1fr; }
            .composer { grid-template-columns: 1fr; }
            .chat-header, .msg-head, .msg-actions, .composer-tools, .meta-list div, .sidebar-header { align-items: flex-start; flex-direction: column; }
            .msg { max-width: 100%; }
        }
        .chat-sidebar, .chat-panel { padding: 20px; }
        .ticket-meta h1 { font-size: 22px; margin-bottom: 6px; }
        .ticket-meta p, .muted, .upload-hint, .attachment-meta, .notify-item small, .status-label, .live-status { color: #94a3b8; }
        .eyebrow { display: block; margin-bottom: 6px; color: #93c5fd; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; }
        .badge { padding: 5px 12px; border-radius: 999px; font-size: 12px; display: inline-block; margin-top: 10px; }
        .badge.open { background: #2563eb; } .badge.pending { background: #f59e0b; } .badge.resolved { background: #22c55e; } .badge.closed { background: #64748b; }
        .meta-list { margin-top: 20px; display: flex; flex-direction: column; gap: 10px; }
        .meta-list div { font-size: 13px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px; gap: 10px; }
        .meta-list span:last-child { text-align: right; color: #f8fafc; }
        .notification-list { margin-top: 15px; display: grid; gap: 10px; max-height: 360px; overflow-y: auto; padding-right: 4px; }
        .notify-item { padding: 12px; border-radius: 12px; background: rgba(255,255,255,0.05); font-size: 12px; display: grid; gap: 5px; }
        .notify-link { color: #bfdbfe; text-decoration: none; line-height: 1.45; }
        .notify-link:hover { text-decoration: underline; }
        .chat-panel { display: flex; flex-direction: column; min-height: 75vh; gap: 16px; }
        .chat-header { gap: 16px; }
        .status-form { display: grid; gap: 8px; justify-items: end; }
        .status-label { font-size: 12px; font-weight: 600; }
        .status-inline { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .chat-stream { flex: 1; overflow-y: auto; padding: 10px 4px; display: flex; flex-direction: column; gap: 14px; }
        .msg { max-width: 76%; padding: 14px; border-radius: 18px; font-size: 14px; animation: fadeIn 0.3s ease; display: grid; gap: 10px; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18); }
        .msg.customer { background: linear-gradient(135deg,#2563eb,#3b82f6); align-self: flex-end; border-bottom-right-radius: 6px; }
        .msg.agent { background: rgba(255,255,255,0.08); align-self: flex-start; border-bottom-left-radius: 6px; }
        .msg.new-message { outline: 2px solid rgba(147, 197, 253, .8); }
        .sender-pill { display: inline-flex; align-items: center; gap: 8px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; background: rgba(255,255,255,0.14); }
        .sender-pill.admin { color: #bfdbfe; }
        .sender-pill.customer { color: #d1fae5; }
        .msg-head { font-size: 11px; opacity: 0.85; gap: 12px; }
        .msg-body { line-height: 1.55; }
        .msg-actions { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
        .message-action { padding: 8px 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.12); background: rgba(15,23,42,0.22); color: #fff; font-size: 12px; cursor: pointer; text-decoration: none; }
        .message-action.delete { border-color: rgba(248,113,113,0.45); color: #fecaca; }
        .message-edit-form { display: grid; gap: 10px; }
        .message-edit-form textarea { min-height: 110px; }
        .attachment-grid { display: grid; gap: 10px; }
        .attachment-card { background: rgba(15,23,42,.22); border: 1px solid rgba(255,255,255,.08); border-radius: 14px; padding: 12px; display: grid; gap: 8px; }
        .attachment-card img, .attachment-card video, .attachment-card audio { width: 100%; border-radius: 10px; background: rgba(15,23,42,.35); }
        .attachment-card a { color: #fff; font-weight: 600; }
        .composer { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: end; }
        .composer-main { display: grid; gap: 12px; }
        .composer textarea, .composer select, .file-picker, .status-form select, .message-edit-form textarea {
            width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.08); color: #f8fafc;
        }
        .composer select, .status-form select { min-width: 180px; appearance: none; }
        select option { color: #0f172a; background: #f8fafc; }
        .file-picker::file-selector-button { margin-right: 12px; border: none; border-radius: 10px; background: #1d4ed8; color: #fff; padding: 8px 12px; cursor: pointer; }
        .composer-tools { gap: 12px; flex-wrap: wrap; }
        .primary-btn, .secondary-btn { border: none; border-radius: 12px; color: white; cursor: pointer; text-decoration: none; }
        .primary-btn { background: linear-gradient(135deg,#2563eb,#3b82f6); padding: 12px 18px; min-width: 140px; }
        .secondary-btn { background: rgba(255,255,255,0.08); padding: 10px 14px; }
        .flash { padding: 12px 14px; border-radius: 12px; }
        .flash.success { background: rgba(34,197,94,.15); color: #bbf7d0; }
        .flash.error { background: rgba(248,113,113,.15); color: #fecaca; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px);} to { opacity: 1; transform: translateY(0);} }
    </style>

    <main class="chat-layout">
        <aside class="chat-sidebar card" aria-label="Ticket details sidebar">
            <a href="<?= $user['role'] === 'admin' ? '/qwe1/admin.php' : '/qwe1/dashboard.php' ?>" class="secondary-btn">← Back</a>
            <div class="ticket-meta">
                <h1><?= e($ticket['subject']) ?></h1>
                <p><?= e($ticket['ticket_code']) ?></p>
                <span class="badge <?= strtolower((string) $ticket['status']) ?>"><?= e($ticket['status']) ?></span>
            </div>
            <div class="meta-list">
                <div><strong>Customer</strong><span><?= e($ticket['name'] ?? 'N/A') ?></span></div>
                <div><strong>Email</strong><span><?= e($ticket['email'] ?? 'N/A') ?></span></div>
                <div><strong>Order</strong><span><?= e($ticket['order_reference'] ?? 'N/A') ?></span></div>
                <div><strong>Priority</strong><span><?= e($ticket['priority'] ?? 'N/A') ?></span></div>
            </div>
            <div class="sidebar-header" style="margin-top:18px; gap:10px;">
                <h2>Recent notifications</h2>
                <form method="post">
                    <input type="hidden" name="action" value="clear_notifications">
                    <button class="secondary-btn" type="submit">Clear</button>
                </form>
            </div>
            <p class="live-status">Auto refresh every 5 seconds.</p>
            <div class="notification-list" id="notification-list">
                <?= renderNotificationList($notificationRows) ?>
            </div>
        </aside>

        <section class="chat-panel card" aria-labelledby="chat-title">
            <?php if (!empty($flash)): ?>
                <div class="flash <?= e($flash['type'] ?? '') ?>"><?= e($flash['message'] ?? '') ?></div>
            <?php endif; ?>

            <header class="chat-header">
                <div>
                    <span class="eyebrow">Professional support conversation</span>
                    <h2 id="chat-title"><?= e($ticket['subject'] ?? 'No Subject') ?></h2>
                </div>
                <?php if ($user['role'] === 'admin'): ?>
                    <form method="post" class="status-form" aria-label="Update ticket status">
                        <input type="hidden" name="action" value="update_status">
                        <label class="status-label" for="ticket-status-top">Ticket status</label>
                        <div class="status-inline">
                            <select id="ticket-status-top" name="status" aria-label="Ticket status">
                                <?php foreach (TICKET_STATUSES as $statusOption): ?>
                                    <option<?= $ticket['status'] === $statusOption ? ' selected' : '' ?>><?= e($statusOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="secondary-btn" type="submit">Update</button>
                        </div>
                    </form>
                <?php endif; ?>
            </header>

            <div class="chat-stream" id="chat-stream" aria-live="polite">
                <?= renderMessageStream($ticket, $user, $editingMessageId, $ticketId) ?>
            </div>

            <form method="post" class="composer" aria-label="Reply composer" enctype="multipart/form-data">
                <input type="hidden" name="action" value="reply">
                <div class="composer-main">
                    <label class="sr-only" for="ticket-message">Message</label>
                    <textarea id="ticket-message" name="message" rows="4" placeholder="Type your reply, update, or resolution note..."></textarea>
                    <div class="composer-tools">
                        <div style="flex:1; min-width:280px;">
                            <label class="sr-only" for="ticket-attachments">Attachments</label>
                            <input id="ticket-attachments" class="file-picker" type="file" name="attachments[]" multiple accept="image/*,audio/*,video/*">
                            <p class="upload-hint">Images are optimized below 200 KB. Audio is optimized below 200 KB. Videos are optimized below 5 MB automatically.</p>
                        </div>
                        <?php if ($user['role'] === 'admin'): ?>
                            <div>
                                <label class="status-label" for="ticket-status-compose">Choose status</label>
                                <select id="ticket-status-compose" name="status" aria-label="Choose status" style="max-width:180px;">
                                    <?php foreach (TICKET_STATUSES as $statusOption): ?>
                                        <option<?= $ticket['status'] === $statusOption ? ' selected' : '' ?>><?= e($statusOption) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="primary-btn" type="submit">Send Reply</button>
            </form>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
<script>
(() => {
    const stream = document.getElementById('chat-stream');
    const notifications = document.getElementById('notification-list');
    if (!stream || !notifications) {
        return;
    }

    let lastMessageId = Number((stream.querySelector('.msg:last-of-type')?.id || 'message-0').replace('message-', '')) || 0;
    let refreshInProgress = false;

    const refreshStream = async () => {
        if (refreshInProgress) {
            return;
        }

        refreshInProgress = true;
        try {
            const response = await fetch(`/qwe1/ticket.php?id=<?= $ticketId ?>&partial=stream`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            });
            if (!response.ok) {
                throw new Error('Failed to refresh ticket stream');
            }

            const data = await response.json();
            if (typeof data.messages_html === 'string') {
                stream.innerHTML = data.messages_html;
                const newestMessage = stream.querySelector('.msg:last-of-type');
                const newestMessageId = Number((newestMessage?.id || 'message-0').replace('message-', '')) || 0;
                if (newestMessage && newestMessageId > lastMessageId) {
                    newestMessage.classList.add('new-message');
                    newestMessage.scrollIntoView({ behavior: 'smooth', block: 'end' });
                    window.setTimeout(() => newestMessage.classList.remove('new-message'), 2500);
                }
                lastMessageId = Math.max(lastMessageId, newestMessageId);
            }

            if (typeof data.notifications_html === 'string') {
                notifications.innerHTML = data.notifications_html;
            }
        } catch (error) {
            console.error(error);
        } finally {
            refreshInProgress = false;
        }
    };

    window.setInterval(refreshStream, 5000);

    notifications.addEventListener('click', (event) => {
        const link = event.target.closest('a[href^="#message-"]');
        if (!link) {
            return;
        }

        event.preventDefault();
        const target = document.querySelector(link.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.add('new-message');
            window.setTimeout(() => target.classList.remove('new-message'), 2500);
            history.replaceState(null, '', link.getAttribute('href'));
        }
    });
})();
</script>
</body>
</html>

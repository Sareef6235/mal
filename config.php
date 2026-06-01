<?php
declare(strict_types=1);

session_start();

const APP_NAME = 'Madrasa ERP OCR Import Center';
const DB_HOST = 'localhost';
const DB_NAME = 'madrasa_erp';
const DB_USER = 'root';
const DB_PASS = '';
const UPLOAD_DIR = __DIR__ . '/uploads';
const MAX_UPLOAD_SIZE_FALLBACK = 10485760;

require_once __DIR__ . '/vendor/autoload.php';

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function get_setting(string $key, mixed $default = null): mixed
{
    $stmt = db()->prepare('SELECT setting_value, setting_type FROM ocr_settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) {
        return $default;
    }
    return match ($row['setting_type']) {
        'boolean' => (bool)$row['setting_value'],
        'integer' => (int)$row['setting_value'],
        'json' => json_decode((string)$row['setting_value'], true) ?: $default,
        default => $row['setting_value'],
    };
}

function save_setting(string $key, mixed $value, string $type = 'string'): void
{
    $stored = $type === 'json' ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : (string)$value;
    $stmt = db()->prepare('INSERT INTO ocr_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)');
    $stmt->execute([$key, $stored, $type]);
}

function log_event(?int $importId, string $level, string $message, array $context = []): void
{
    $stmt = db()->prepare('INSERT INTO ocr_logs (import_id, level, message, context, ip_address) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$importId, $level, $message, json_encode($context, JSON_UNESCAPED_UNICODE), $_SERVER['REMOTE_ADDR'] ?? null]);
}

function normalize_ocr_text(string $text): string
{
    if (get_setting('remove_extra_spaces', true)) {
        $text = preg_replace('/[\t ]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\R{3,}/u', "\n\n", $text) ?? $text;
    }
    return trim($text);
}

function validate_regex(string $pattern, string $flags = 'miu'): string
{
    $delimited = '~' . str_replace('~', '\\~', $pattern) . '~' . preg_replace('/[^imsxuADSUXJ]/', '', $flags);
    set_error_handler(static fn() => true);
    $ok = preg_match($delimited, '') !== false;
    restore_error_handler();
    if (!$ok) {
        throw new InvalidArgumentException('Regex pattern is invalid.');
    }
    return $delimited;
}

function parse_rows_with_rule(string $text, array $rule): array
{
    $regex = validate_regex($rule['pattern'], $rule['flags'] ?? 'miu');
    preg_match_all($regex, $text, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);
    $rows = [];
    foreach ($matches as $index => $match) {
        $rows[] = [
            'row_index' => $index + 1,
            'month' => $match['month'] ?? '',
            'class_name' => $match['class'] ?? $match['class_name'] ?? '',
            'week' => $match['week'] ?? '',
            'total_period' => $match['total_period'] ?? $match['period'] ?? '',
            'subject' => $match['subject'] ?? '',
            'lesson_name' => $match['lesson_name'] ?? $match['lesson'] ?? '',
            'lesson_details' => $match['lesson_details'] ?? $match['details'] ?? '',
            'activities' => $match['activities'] ?? '',
            'smart_date' => normalize_date($match['smart_date'] ?? $match['date'] ?? null),
            'exam_date' => normalize_date($match['exam_date'] ?? null),
            'raw_match' => $match[0] ?? '',
            'validation_errors' => [],
            'status' => 'ready',
        ];
    }
    return $rows;
}

function normalize_date(?string $value): ?string
{
    if (!$value) {
        return null;
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d', $ts) : null;
}

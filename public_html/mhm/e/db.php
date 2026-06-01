<?php
declare(strict_types=1);

session_start();

const APP_NAME = 'OCR Text Extractor Pro';
const MAX_UPLOAD_BYTES = 15728640; // 15 MB, safe for shared hosting.
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
const ALLOWED_MIME_TYPES = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'application/pdf',
    'application/x-pdf',
];

$CONFIG = [
    'db_host' => getenv('OCR_DB_HOST') ?: 'localhost',
    'db_name' => getenv('OCR_DB_NAME') ?: 'ocr_text_extractor',
    'db_user' => getenv('OCR_DB_USER') ?: 'root',
    'db_pass' => getenv('OCR_DB_PASS') ?: '',
    'db_charset' => 'utf8mb4',
];

function db(): PDO
{
    static $pdo = null;
    global $CONFIG;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $CONFIG['db_host'],
        $CONFIG['db_name'],
        $CONFIG['db_charset']
    );

    $pdo = new PDO($dsn, $CONFIG['db_user'], $CONFIG['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    ensure_schema($pdo);
    return $pdo;
}

function ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS regex_rules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            pattern VARCHAR(500) NOT NULL,
            replacement TEXT NULL,
            flags VARCHAR(20) NOT NULL DEFAULT "u",
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS upload_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(120) NOT NULL,
            file_size INT UNSIGNED NOT NULL,
            raw_text MEDIUMTEXT NULL,
            cleaned_text MEDIUMTEXT NULL,
            status VARCHAR(40) NOT NULL DEFAULT "uploaded",
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(['success' => false, 'message' => 'Security token expired. Refresh the page and try again.'], 419);
    }
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function uploads_path(string $filename = ''): string
{
    $base = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }
    return $filename === '' ? $base : $base . DIRECTORY_SEPARATOR . $filename;
}

function command_exists(string $command): bool
{
    $result = trim((string) shell_exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null'));
    return $result !== '';
}

function get_enabled_rules(): array
{
    $stmt = db()->query('SELECT * FROM regex_rules WHERE enabled = 1 ORDER BY sort_order ASC, id ASC');
    return $stmt->fetchAll();
}

function wrap_pattern(string $pattern, string $flags): string
{
    $flags = preg_replace('/[^imsxuADSUXJ]/', '', $flags) ?: 'u';
    if (@preg_match($pattern, '') !== false) {
        return $pattern;
    }
    return '~' . str_replace('~', '\\~', $pattern) . '~' . $flags;
}

function apply_regex_rules(string $text, ?array $rules = null): array
{
    $rules = $rules ?? get_enabled_rules();
    $cleaned = $text;
    $applied = [];

    foreach ($rules as $rule) {
        $pattern = wrap_pattern((string) $rule['pattern'], (string) ($rule['flags'] ?? 'u'));
        $replacement = (string) ($rule['replacement'] ?? '');
        $count = 0;
        $next = @preg_replace($pattern, $replacement, $cleaned, -1, $count);

        if ($next === null) {
            $applied[] = ['name' => $rule['name'], 'count' => 0, 'error' => 'Invalid pattern'];
            continue;
        }

        $cleaned = $next;
        $applied[] = ['name' => $rule['name'], 'count' => $count, 'error' => null];
    }

    return ['text' => trim($cleaned), 'applied' => $applied];
}

function extract_text_from_file(string $path, string $mime): array
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'pdf' || str_contains($mime, 'pdf')) {
        return extract_pdf_text($path);
    }
    return extract_image_text($path);
}

function extract_pdf_text(string $path): array
{
    if (!command_exists('pdftotext')) {
        return [
            'success' => false,
            'text' => '',
            'message' => 'pdftotext is not installed on this server. Install Poppler utilities or add a PHP PDF parser in /vendor/.',
        ];
    }

    $cmd = 'pdftotext -layout -enc UTF-8 ' . escapeshellarg($path) . ' - 2>&1';
    $text = (string) shell_exec($cmd);
    return ['success' => trim($text) !== '', 'text' => normalize_text($text), 'message' => trim($text) === '' ? 'No readable PDF text found.' : 'PDF text extracted.'];
}

function extract_image_text(string $path): array
{
    if (!command_exists('tesseract')) {
        return [
            'success' => false,
            'text' => '',
            'message' => 'Tesseract OCR is not installed on this server. Ask your host to enable tesseract or install it on VPS hosting.',
        ];
    }

    $cmd = 'tesseract ' . escapeshellarg($path) . ' stdout --psm 6 2>&1';
    $text = (string) shell_exec($cmd);
    return ['success' => trim($text) !== '', 'text' => normalize_text($text), 'message' => trim($text) === '' ? 'No text could be detected in this image.' : 'Image OCR completed.'];
}

function normalize_text(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/[\t ]+/', ' ', $text) ?? $text;
    $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
    return trim($text);
}

function recent_history(int $limit = 20): array
{
    $stmt = db()->prepare('SELECT id, original_name, mime_type, file_size, status, created_at, CHAR_LENGTH(raw_text) AS raw_length, CHAR_LENGTH(cleaned_text) AS cleaned_length FROM upload_history ORDER BY id DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

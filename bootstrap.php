<?php
declare(strict_types=1);

$config = [
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'sqlite', // sqlite|mysql
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'hvernued_p2',
        'user' => getenv('DB_USER') ?: 'hvernued_cpses_hvnqmd5ph8',
        'pass' => getenv('DB_PASS') ?: 'Zirect@1618*1##',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        'sqlite_path' => __DIR__ . '/data/madrasa.sqlite',
    ],
    'app' => [
        'site_title' => 'Premium Madrasa Student Result Management System',
        'default_subject_max_mark' => 50.0,
        'default_subject_pass_mark' => 18.0,
        'sheet_timeout_seconds' => 25,
    ],
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function db_connect(array $cfg): ?PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        if (($cfg['driver'] ?? 'sqlite') === 'mysql') {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['name'], $cfg['charset']);
            $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            $dbPath = $cfg['sqlite_path'] ?? (__DIR__ . '/data/madrasa.sqlite');
            if (!is_dir(dirname($dbPath))) {
                mkdir(dirname($dbPath), 0777, true);
            }
            $pdo = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        ensure_import_tables($pdo);
        return $pdo;
    } catch (Throwable) {
        return null;
    }
}

function db_driver(PDO $db): string
{
    return (string)$db->getAttribute(PDO::ATTR_DRIVER_NAME);
}

function ensure_import_tables(PDO $db): void
{
    $driver = db_driver($db);

    if ($driver === 'sqlite') {
        $db->exec('CREATE TABLE IF NOT EXISTS settings (setting_key TEXT PRIMARY KEY, setting_value TEXT)');
        $db->exec('CREATE TABLE IF NOT EXISTS subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT UNIQUE, subject_name TEXT, max_mark REAL DEFAULT 50, pass_mark REAL DEFAULT 18, display_order INTEGER DEFAULT 1)');
        $db->exec('CREATE TABLE IF NOT EXISTS marks (id INTEGER PRIMARY KEY AUTOINCREMENT, exam_id INTEGER DEFAULT 0, student_id INTEGER NOT NULL, subject_id INTEGER NOT NULL, mark REAL DEFAULT 0, UNIQUE(exam_id, student_id, subject_id))');

        $cols = $db->query('PRAGMA table_info(students)')->fetchAll();
        $haveRegisterNo = false;
        foreach ($cols as $c) {
            if (($c['name'] ?? '') === 'register_no') $haveRegisterNo = true;
        }
        if (!$haveRegisterNo) {
            $db->exec('ALTER TABLE students ADD COLUMN register_no TEXT');
            $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_students_register_no ON students(register_no)');
        }
    } else {
        $db->exec('CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(120) PRIMARY KEY, setting_value TEXT)');
        $db->exec('CREATE TABLE IF NOT EXISTS subjects (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(120) UNIQUE, subject_name VARCHAR(180), max_mark DECIMAL(8,2) DEFAULT 50, pass_mark DECIMAL(8,2) DEFAULT 18, display_order INT DEFAULT 1)');
        $db->exec('CREATE TABLE IF NOT EXISTS marks (id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT DEFAULT 0, student_id INT NOT NULL, subject_id INT NOT NULL, mark DECIMAL(8,2) DEFAULT 0, UNIQUE KEY uk_exam_student_sub(exam_id, student_id, subject_id))');

        try { $db->exec('ALTER TABLE students ADD COLUMN register_no VARCHAR(120) UNIQUE'); } catch (Throwable) {}
    }
}

function setting(PDO $db, string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $rows = $db->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        foreach ($rows as $row) {
            $cache[(string)$row['setting_key']] = (string)($row['setting_value'] ?? '');
        }
    }

    return $cache[$key] ?? $default;
}

function save_setting(PDO $db, string $key, string $value): void
{
    $driver = db_driver($db);
    if ($driver === 'sqlite') {
        $st = $db->prepare('INSERT INTO settings(setting_key, setting_value) VALUES(:k, :v) ON CONFLICT(setting_key) DO UPDATE SET setting_value=:v');
    } else {
        $st = $db->prepare('INSERT INTO settings(setting_key, setting_value) VALUES(:k, :v) ON DUPLICATE KEY UPDATE setting_value=:v');
    }
    $st->execute(['k' => $key, 'v' => $value]);
}

function normalize_sheet_url(string $url): string
{
    $url = trim($url);
    if ($url === '') return '';

    if (str_contains($url, '/spreadsheets/d/')) {
        $parsed = parse_url($url);
        $path = (string)($parsed['path'] ?? '');
        if (preg_match('#/spreadsheets/d/([^/]+)#', $path, $m)) {
            $sheetId = $m[1];
            $gid = '0';
            parse_str((string)($parsed['query'] ?? ''), $q);
            if (!empty($q['gid'])) $gid = (string)$q['gid'];
            if (!empty($parsed['fragment']) && preg_match('/gid=(\d+)/', (string)$parsed['fragment'], $fm)) $gid = $fm[1];
            return sprintf('https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s', rawurlencode($sheetId), rawurlencode($gid));
        }
    }

    if (str_contains($url, '/pubhtml')) $url = str_replace('/pubhtml', '/pub', $url);
    if (str_contains($url, '/pub?') || str_ends_with($url, '/pub')) {
        if (!str_contains($url, 'output=csv')) $url .= (str_contains($url, '?') ? '&' : '?') . 'output=csv';
    }

    return $url;
}

function fetch_csv_rows(string $url, int $timeout = 25): array
{
    if ($url === '') return [];

    $url = normalize_sheet_url($url);
    $ctx = stream_context_create(['http' => ['timeout' => $timeout], 'https' => ['timeout' => $timeout]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => $timeout]);
        $raw = curl_exec($ch);
        curl_close($ch);
    }
    if (!$raw) return [];

    $fp = fopen('php://temp', 'r+');
    if ($fp === false) return [];
    fwrite($fp, $raw);
    rewind($fp);

    $header = fgetcsv($fp);
    if (!$header) { fclose($fp); return []; }
    $header = array_map(fn($h) => strtolower(trim((string)$h)), $header);

    $rows = [];
    while (($line = fgetcsv($fp)) !== false) {
        if (!array_filter($line, fn($v) => trim((string)$v) !== '')) continue;
        $rows[] = array_combine($header, array_pad($line, count($header), ''));
    }
    fclose($fp);

    return $rows;
}

function render_header(string $title): void
{
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . e($title) . '</title>';
    echo '<style>:root{--bg:#0f172a;--bg2:#1e293b;--glass:rgba(255,255,255,.14);--line:rgba(255,255,255,.24);--text:#f8fafc;--muted:#cbd5e1;--accent:#7c3aed;--ok:#22c55e;--bad:#ef4444}*{box-sizing:border-box}body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;color:var(--text);background:radial-gradient(circle at 10% 10%,#1d4ed8 0%,transparent 40%),radial-gradient(circle at 90% 0%,#9333ea 0%,transparent 35%),linear-gradient(135deg,var(--bg),#020617)}.container{max-width:1120px;margin:24px auto;padding:0 14px 24px}.hero{padding:24px;border-radius:16px;background:var(--glass);backdrop-filter:blur(12px);border:1px solid var(--line);box-shadow:0 15px 40px rgba(0,0,0,.25)}.nav{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}.nav a{padding:10px 14px;border-radius:10px;background:rgba(124,58,237,.3);border:1px solid rgba(196,181,253,.4);text-decoration:none;color:#fff}.card{margin-top:16px;padding:16px;border-radius:14px;background:var(--glass);backdrop-filter:blur(10px);border:1px solid var(--line)}input,select,button{width:100%;padding:11px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.26);background:rgba(15,23,42,.6);color:#fff}button{cursor:pointer;background:linear-gradient(120deg,#4f46e5,#7c3aed)}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.18);text-align:left}.small{color:var(--muted);font-size:.93rem}.msg-ok{color:#86efac}.msg-bad{color:#fecaca}</style>';
    echo '</head><body><main class="container">';
    echo '<section class="hero"><h1>' . e($title) . '</h1><p class="small">Secure • Google Sheet Sync • CSV Import</p><nav class="nav"><a href="index.php">Portal</a><a href="admin_bulk_upload.php">Bulk Upload</a></nav></section>';
}

function render_footer(): void
{
    echo '</main></body></html>';
}

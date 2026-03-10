<?php
declare(strict_types=1);

$dbTypeFile = __DIR__ . '/db_config.php';
$selected = is_file($dbTypeFile) ? include $dbTypeFile : [];
$dbConfig = (is_array($selected) && is_array($selected['db'] ?? null)) ? $selected['db'] : [];
$dbType = (is_array($selected) && in_array(($selected['db_type'] ?? ''), ['sqlite', 'mysql'], true))
    ? (string)$selected['db_type']
    : ((is_array($dbConfig) && in_array(($dbConfig['driver'] ?? ''), ['sqlite', 'mysql'], true)) ? (string)$dbConfig['driver'] : null);

$config = [
    'db' => [
        'driver' => $dbType ?: (string)($dbConfig['driver'] ?? (getenv('DB_DRIVER') ?: 'sqlite')),
        'host' => (string)($dbConfig['host'] ?? (getenv('DB_HOST') ?: 'localhost')),
        'name' => (string)($dbConfig['name'] ?? (getenv('DB_NAME') ?: 'hvernued_p2')),
        'user' => (string)($dbConfig['user'] ?? (getenv('DB_USER') ?: 'hvernued_cpses_hvnqmd5ph8')),
        'pass' => (string)($dbConfig['pass'] ?? (getenv('DB_PASS') ?: 'Zirect@1618*1##')),
        'charset' => (string)($dbConfig['charset'] ?? (getenv('DB_CHARSET') ?: 'utf8mb4')),
        'sqlite_path' => (string)($dbConfig['sqlite_path'] ?? (__DIR__ . '/data/madrasa.sqlite')),
    ],
    'app' => [
        'site_title' => 'Premium Madrasa Student Result Management System',
        'default_subject_max_mark' => 50.0,
        'default_subject_pass_mark' => 18.0,
        'sheet_timeout_seconds' => 25,
    ],
];

const MAX_IMAGE_UPLOAD_BYTES = 204800;

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

function validate_image_upload(array $file, int $maxBytes = MAX_IMAGE_UPLOAD_BYTES): array {
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) return [false, 'Image upload failed.'];
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) return [false, 'Image must be 200KB or less.'];
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) return [false, 'Invalid upload source.'];
    $mime = mime_content_type($tmp) ?: '';
    if (!str_starts_with($mime, 'image/')) return [false, 'Only image uploads are allowed.'];
    return [true, ''];
}

function ensure_photo_directory(): string {
    $dir = __DIR__ . '/photos';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    return $dir;
}

function db_connect(array $cfg): ?PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    try {
        if (($cfg['driver'] ?? 'sqlite') === 'mysql') {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['name'], $cfg['charset']);
            $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        } else {
            $dbPath = $cfg['sqlite_path'] ?? (__DIR__ . '/data/madrasa.sqlite');
            if (!is_dir(dirname($dbPath))) mkdir(dirname($dbPath), 0777, true);
            $pdo = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        }
        ensure_import_tables($pdo);
        return $pdo;
    } catch (Throwable) { return null; }
}

function db_driver(PDO $db): string { return (string)$db->getAttribute(PDO::ATTR_DRIVER_NAME); }

function student_name_sql(): string { return 'COALESCE(NULLIF(full_name, ""), NULLIF(name, ""), "Unknown")'; }

function ensure_import_tables(PDO $db): void {
    $driver = db_driver($db);
    if ($driver === 'sqlite') {
        $db->exec('CREATE TABLE IF NOT EXISTS students (id INTEGER PRIMARY KEY AUTOINCREMENT, student_uid TEXT UNIQUE, register_no TEXT UNIQUE, full_name TEXT, class_name TEXT, gender TEXT, photo_path TEXT, attendance_percent REAL DEFAULT 0, madrasa_id INTEGER DEFAULT 1, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS teachers (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, full_name TEXT, subject_name TEXT, class_name TEXT, attendance_percent REAL DEFAULT 100, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS settings (setting_key TEXT PRIMARY KEY, setting_value TEXT)');
        $db->exec('CREATE TABLE IF NOT EXISTS exams (id INTEGER PRIMARY KEY, madrasa_id INTEGER DEFAULT 1, exam_name TEXT, exam_type TEXT, exam_date TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT UNIQUE, subject_name TEXT, max_mark REAL DEFAULT 50, pass_mark REAL DEFAULT 18, display_order INTEGER DEFAULT 1)');
        $db->exec('CREATE TABLE IF NOT EXISTS marks (id INTEGER PRIMARY KEY AUTOINCREMENT, exam_id INTEGER DEFAULT 0, student_id INTEGER NOT NULL, subject_id INTEGER NOT NULL, mark REAL DEFAULT 0, UNIQUE(exam_id, student_id, subject_id))');
        $db->exec('CREATE TABLE IF NOT EXISTS admissions (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_name TEXT, student_name TEXT, guardian_name TEXT, phone TEXT, class_name TEXT, address TEXT, photo_path TEXT, status TEXT DEFAULT "Pending", created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS fees (id INTEGER PRIMARY KEY AUTOINCREMENT, student_id INTEGER NULL, student_name TEXT, madrasa_name TEXT, month_key TEXT, amount REAL DEFAULT 0, paid_amount REAL DEFAULT 0, due_amount REAL DEFAULT 0, status TEXT DEFAULT "Pending", paid_at TEXT DEFAULT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS self_profiles (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, msr_no TEXT NOT NULL UNIQUE, address TEXT NOT NULL, place TEXT NOT NULL, work_madrasa TEXT NOT NULL, phone TEXT NOT NULL, qr_token TEXT NOT NULL UNIQUE, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS attendance_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, profile_id INTEGER NOT NULL, name TEXT NOT NULL, msr_no TEXT NOT NULL, phone TEXT NOT NULL, place TEXT NOT NULL, attended_at TEXT DEFAULT CURRENT_TIMESTAMP)');

        // Festival ERP tables
        $db->exec('CREATE TABLE IF NOT EXISTS festivals (id INTEGER PRIMARY KEY AUTOINCREMENT, festival_name TEXT NOT NULL, festival_type TEXT NOT NULL, year INTEGER NOT NULL, start_date TEXT, end_date TEXT, status TEXT DEFAULT "Active", is_active INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS festival_categories (id INTEGER PRIMARY KEY AUTOINCREMENT, category_name TEXT UNIQUE NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS festival_events (id INTEGER PRIMARY KEY AUTOINCREMENT, event_name TEXT NOT NULL, festival_id INTEGER NOT NULL, category_id INTEGER NOT NULL, max_score REAL DEFAULT 100, event_date TEXT, venue TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS houses (id INTEGER PRIMARY KEY AUTOINCREMENT, house_name TEXT UNIQUE NOT NULL, color_code TEXT DEFAULT "#2563eb", created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS festival_participants (id INTEGER PRIMARY KEY AUTOINCREMENT, participant_type TEXT DEFAULT "student", name TEXT NOT NULL, register_no TEXT, class_name TEXT, gender TEXT, event_id INTEGER NOT NULL, category_id INTEGER NOT NULL, house_id INTEGER NULL, qr_token TEXT UNIQUE, checked_in INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS festival_judges (id INTEGER PRIMARY KEY AUTOINCREMENT, judge_name TEXT NOT NULL, festival_id INTEGER NOT NULL, event_id INTEGER NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS festival_scores (id INTEGER PRIMARY KEY AUTOINCREMENT, participant_id INTEGER NOT NULL, event_id INTEGER NOT NULL, judge_id INTEGER NOT NULL, score REAL NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(participant_id, judge_id))');
        $db->exec('CREATE TABLE IF NOT EXISTS house_points (id INTEGER PRIMARY KEY AUTOINCREMENT, house_id INTEGER NOT NULL, participant_id INTEGER NOT NULL, event_id INTEGER NOT NULL, points INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');

        $seedCategories = ['Boys','Girls','General','LP','UP','HS','Open'];
        $seedHouses = [['Green House','#16a34a'],['Blue House','#2563eb'],['Red House','#dc2626'],['Yellow House','#ca8a04']];
        $catStmt = $db->prepare('INSERT OR IGNORE INTO festival_categories(category_name) VALUES(?)');
        foreach ($seedCategories as $cat) $catStmt->execute([$cat]);
        $houseStmt = $db->prepare('INSERT OR IGNORE INTO houses(house_name,color_code) VALUES(?,?)');
        foreach ($seedHouses as $h) $houseStmt->execute([$h[0],$h[1]]);

        $cols = $db->query('PRAGMA table_info(students)')->fetchAll();
        $haveRegisterNo = false;
        $haveRegisterNumber = false;
        $haveFullName = false;
        $haveName = false;
        $haveClassName = false;
        $haveClassId = false;
        $haveClassRank = false;
        $haveOverallRank = false;
        foreach ($cols as $c) if (($c['name'] ?? '') === 'register_no') $haveRegisterNo = true;
        foreach ($cols as $c) {
            if (($c['name'] ?? '') === 'full_name') $haveFullName = true;
            if (($c['name'] ?? '') === 'name') $haveName = true;
            if (($c['name'] ?? '') === 'register_number') $haveRegisterNumber = true;
            if (($c['name'] ?? '') === 'class_name') $haveClassName = true;
            if (($c['name'] ?? '') === 'class_id') $haveClassId = true;
            if (($c['name'] ?? '') === 'class_rank') $haveClassRank = true;
            if (($c['name'] ?? '') === 'overall_rank') $haveOverallRank = true;
        }
        if (!$haveRegisterNo) {
            $db->exec('ALTER TABLE students ADD COLUMN register_no TEXT');
            $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_students_register_no ON students(register_no)');
        }
        if (!$haveFullName) {
            $db->exec('ALTER TABLE students ADD COLUMN full_name TEXT');
        }
        if (!$haveName) {
            $db->exec('ALTER TABLE students ADD COLUMN name TEXT');
        }
        if (!$haveRegisterNumber) {
            $db->exec('ALTER TABLE students ADD COLUMN register_number TEXT');
        }
        if (!$haveClassName) {
            $db->exec('ALTER TABLE students ADD COLUMN class_name TEXT');
        }
        if (!$haveClassId) {
            $db->exec('ALTER TABLE students ADD COLUMN class_id INTEGER');
        }
        if (!$haveClassRank) {
            $db->exec('ALTER TABLE students ADD COLUMN class_rank INTEGER');
        }
        if (!$haveOverallRank) {
            $db->exec('ALTER TABLE students ADD COLUMN overall_rank INTEGER');
        }
        $db->exec('UPDATE students SET register_no = COALESCE(NULLIF(register_no, ""), register_number), register_number = COALESCE(NULLIF(register_number, ""), register_no)');
        $db->exec('UPDATE students SET full_name = COALESCE(NULLIF(full_name, ""), name), name = COALESCE(NULLIF(name, ""), full_name)');
    } else {
        $db->exec('CREATE TABLE IF NOT EXISTS students (id INT AUTO_INCREMENT PRIMARY KEY, student_uid VARCHAR(40) UNIQUE, register_no VARCHAR(120) UNIQUE, full_name VARCHAR(191), class_name VARCHAR(80), gender VARCHAR(20), photo_path VARCHAR(255), attendance_percent DECIMAL(5,2) DEFAULT 0, madrasa_id INT DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS teachers (id INT AUTO_INCREMENT PRIMARY KEY, madrasa_id INT DEFAULT 1, full_name VARCHAR(191), subject_name VARCHAR(120), class_name VARCHAR(80), attendance_percent DECIMAL(5,2) DEFAULT 100, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(120) PRIMARY KEY, setting_value TEXT)');
        $db->exec('CREATE TABLE IF NOT EXISTS exams (id INT PRIMARY KEY, madrasa_id INT DEFAULT 1, exam_name VARCHAR(191), exam_type VARCHAR(80), exam_date DATE NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS subjects (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(120) UNIQUE, subject_name VARCHAR(180), max_mark DECIMAL(8,2) DEFAULT 50, pass_mark DECIMAL(8,2) DEFAULT 18, display_order INT DEFAULT 1)');
        $db->exec('CREATE TABLE IF NOT EXISTS marks (id INT AUTO_INCREMENT PRIMARY KEY, exam_id INT DEFAULT 0, student_id INT NOT NULL, subject_id INT NOT NULL, mark DECIMAL(8,2) DEFAULT 0, UNIQUE KEY uk_exam_student_sub(exam_id, student_id, subject_id))');
        $db->exec('CREATE TABLE IF NOT EXISTS admissions (id INT AUTO_INCREMENT PRIMARY KEY, madrasa_name VARCHAR(191), student_name VARCHAR(191), guardian_name VARCHAR(191), phone VARCHAR(50), class_name VARCHAR(80), address VARCHAR(255), photo_path VARCHAR(255), status VARCHAR(30) DEFAULT "Pending", created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS fees (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NULL, student_name VARCHAR(191), madrasa_name VARCHAR(191), month_key VARCHAR(7), amount DECIMAL(10,2) DEFAULT 0, paid_amount DECIMAL(10,2) DEFAULT 0, due_amount DECIMAL(10,2) DEFAULT 0, status VARCHAR(30) DEFAULT "Pending", paid_at DATETIME NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS self_profiles (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(191) NOT NULL, msr_no VARCHAR(100) NOT NULL UNIQUE, address VARCHAR(255) NOT NULL, place VARCHAR(191) NOT NULL, work_madrasa VARCHAR(191) NOT NULL, phone VARCHAR(50) NOT NULL, qr_token VARCHAR(64) NOT NULL UNIQUE, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS attendance_logs (id INT AUTO_INCREMENT PRIMARY KEY, profile_id INT NOT NULL, name VARCHAR(191) NOT NULL, msr_no VARCHAR(100) NOT NULL, phone VARCHAR(50) NOT NULL, place VARCHAR(191) NOT NULL, attended_at DATETIME DEFAULT CURRENT_TIMESTAMP)');

        // Festival ERP tables
        $db->exec('CREATE TABLE IF NOT EXISTS festivals (id INT AUTO_INCREMENT PRIMARY KEY, festival_name VARCHAR(191) NOT NULL, festival_type VARCHAR(80) NOT NULL, year INT NOT NULL, start_date DATE NULL, end_date DATE NULL, status VARCHAR(30) DEFAULT "Active", is_active TINYINT(1) DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS festival_categories (id INT AUTO_INCREMENT PRIMARY KEY, category_name VARCHAR(80) UNIQUE NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS festival_events (id INT AUTO_INCREMENT PRIMARY KEY, event_name VARCHAR(191) NOT NULL, festival_id INT NOT NULL, category_id INT NOT NULL, max_score DECIMAL(8,2) DEFAULT 100, event_date DATE NULL, venue VARCHAR(191) NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS houses (id INT AUTO_INCREMENT PRIMARY KEY, house_name VARCHAR(80) UNIQUE NOT NULL, color_code VARCHAR(20) DEFAULT "#2563eb", created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS festival_participants (id INT AUTO_INCREMENT PRIMARY KEY, participant_type VARCHAR(20) DEFAULT "student", name VARCHAR(191) NOT NULL, register_no VARCHAR(120) NULL, class_name VARCHAR(80) NULL, gender VARCHAR(20) NULL, event_id INT NOT NULL, category_id INT NOT NULL, house_id INT NULL, qr_token VARCHAR(80) UNIQUE, checked_in TINYINT(1) DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS festival_judges (id INT AUTO_INCREMENT PRIMARY KEY, judge_name VARCHAR(191) NOT NULL, festival_id INT NOT NULL, event_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');
        $db->exec('CREATE TABLE IF NOT EXISTS festival_scores (id INT AUTO_INCREMENT PRIMARY KEY, participant_id INT NOT NULL, event_id INT NOT NULL, judge_id INT NOT NULL, score DECIMAL(8,2) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uk_participant_judge(participant_id, judge_id))');
        $db->exec('CREATE TABLE IF NOT EXISTS house_points (id INT AUTO_INCREMENT PRIMARY KEY, house_id INT NOT NULL, participant_id INT NOT NULL, event_id INT NOT NULL, points INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)');

        $seedCategories = ['Boys','Girls','General','LP','UP','HS','Open'];
        $seedHouses = [['Green House','#16a34a'],['Blue House','#2563eb'],['Red House','#dc2626'],['Yellow House','#ca8a04']];
        $catStmt = $db->prepare('INSERT INTO festival_categories(category_name) VALUES(?) ON DUPLICATE KEY UPDATE category_name=VALUES(category_name)');
        foreach ($seedCategories as $cat) $catStmt->execute([$cat]);
        $houseStmt = $db->prepare('INSERT INTO houses(house_name,color_code) VALUES(?,?) ON DUPLICATE KEY UPDATE color_code=VALUES(color_code)');
        foreach ($seedHouses as $h) $houseStmt->execute([$h[0],$h[1]]);
        try { $db->exec('ALTER TABLE students ADD COLUMN register_no VARCHAR(120) UNIQUE'); } catch (Throwable) {}
        try { $db->exec('ALTER TABLE students ADD COLUMN full_name VARCHAR(191) NULL'); } catch (Throwable) {}
        try { $db->exec('ALTER TABLE students ADD COLUMN name VARCHAR(191) NULL'); } catch (Throwable) {}
        try { $db->exec('ALTER TABLE students ADD COLUMN register_number VARCHAR(120) NULL'); } catch (Throwable) {}
        try { $db->exec('ALTER TABLE students ADD COLUMN class_name VARCHAR(80) NULL'); } catch (Throwable) {}
        try { $db->exec('ALTER TABLE students ADD COLUMN class_id INT NULL'); } catch (Throwable) {}
        try { $db->exec('ALTER TABLE students ADD COLUMN class_rank INT NULL'); } catch (Throwable) {}
        try { $db->exec('ALTER TABLE students ADD COLUMN overall_rank INT NULL'); } catch (Throwable) {}
        $db->exec('UPDATE students SET register_no = COALESCE(NULLIF(register_no, ""), register_number), register_number = COALESCE(NULLIF(register_number, ""), register_no)');
        $db->exec('UPDATE students SET full_name = COALESCE(NULLIF(full_name, ""), name), name = COALESCE(NULLIF(name, ""), full_name)');
    }
}

function setting(PDO $db, string $key, string $default = ''): string {
    $st = $db->prepare('SELECT setting_value FROM settings WHERE setting_key=:key LIMIT 1');
    $st->execute(['key' => $key]);
    $value = $st->fetchColumn();
    return ($value === false || $value === null) ? $default : (string)$value;
}

function save_setting(PDO $db, string $key, string $value): void {
    $driver = db_driver($db);
    $sql = $driver === 'sqlite'
        ? 'INSERT INTO settings(setting_key, setting_value) VALUES(:k, :v) ON CONFLICT(setting_key) DO UPDATE SET setting_value=:v'
        : 'INSERT INTO settings(setting_key, setting_value) VALUES(:k, :v) ON DUPLICATE KEY UPDATE setting_value=:v';
    $st = $db->prepare($sql); $st->execute(['k' => $key, 'v' => $value]);
}

function build_csv_url_from_sheet_id(string $sheetId, string $gid = '0'): string {
    return sprintf('https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s', rawurlencode(trim($sheetId)), rawurlencode(trim($gid) !== '' ? trim($gid) : '0'));
}

function normalize_sheet_url(string $url): string {
    $url = trim($url); if ($url === '') return '';
    if (str_contains($url, '/spreadsheets/d/')) {
        $parsed = parse_url($url); $path = (string)($parsed['path'] ?? '');
        if (preg_match('#/spreadsheets/d/([^/]+)#', $path, $m)) {
            $sheetId = $m[1]; $gid = '0'; parse_str((string)($parsed['query'] ?? ''), $q); if (!empty($q['gid'])) $gid = (string)$q['gid'];
            if (!empty($parsed['fragment']) && preg_match('/gid=(\d+)/', (string)$parsed['fragment'], $fm)) $gid = $fm[1];
            return build_csv_url_from_sheet_id($sheetId, $gid);
        }
    }
    if (str_contains($url, '/pubhtml')) $url = str_replace('/pubhtml', '/pub', $url);
    if ((str_contains($url, '/pub?') || str_ends_with($url, '/pub')) && !str_contains($url, 'output=csv')) $url .= (str_contains($url, '?') ? '&' : '?') . 'output=csv';
    return $url;
}

function fetch_csv_rows(string $url, int $timeout = 25): array {
    if ($url === '') return [];
    $url = normalize_sheet_url($url);
    $ctx = stream_context_create(['http' => ['timeout' => $timeout], 'https' => ['timeout' => $timeout]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false && function_exists('curl_init')) {
        $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => $timeout]); $raw = curl_exec($ch); curl_close($ch);
    }
    if (!$raw) return [];
    $fp = fopen('php://temp', 'r+'); if ($fp === false) return [];
    fwrite($fp, $raw);
    rewind($fp);

    $header = fgetcsv($fp, 0, ",", '"', "\\");

    if (!$header) { fclose($fp); return []; }
    $header = array_map(fn($h) => strtolower(trim((string)$h)), $header); $rows = [];
    while (($line = fgetcsv($fp, 0, ",", '"', "\\")) !== false) { if (!array_filter($line, fn($v) => trim((string)$v) !== '')) continue; $rows[] = array_combine($header, array_pad($line, count($header), '')); }
    fclose($fp); return $rows;
}

function app_base_dir(): string {
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '/');
    $dir = str_replace('\\', '/', dirname($script));
    return ($dir === '.' || $dir === '\\' || $dir === '/') ? '' : '/' . trim($dir, '/');
}
function app_base_url(): string {
    $scheme = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? strtolower(trim((string)$_SERVER['HTTP_X_FORWARDED_PROTO'])) : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'));
    $host = (string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000');
    return $scheme . '://' . $host . app_base_dir();
}
function app_page_url(string $page, array $query = []): string { $url = rtrim(app_base_url(), '/') . '/' . ltrim($page, '/'); if ($query) $url .= '?' . http_build_query($query); return $url; }
function attendance_base_url(): string { $forced = trim((string)(getenv('ATTENDANCE_BASE_URL') ?: '')); return $forced !== '' ? rtrim($forced, '/') : app_page_url('attendance_qr.php'); }
function attendance_scan_url(string $token): string { return trim($token) === '' ? attendance_base_url() : attendance_base_url() . '?self_qr=' . urlencode(trim($token)); }
function attendance_scan_msr_url(string $msrNo): string { return trim($msrNo) === '' ? attendance_base_url() : attendance_base_url() . '?scan=' . urlencode(trim($msrNo)); }



function fetch_students_for_id_cards(PDO $db, string $className = '', int $limit = 1000): array {
    $limit = max(1, min(1000, $limit));
    if ($className !== '') {
        $st = $db->prepare('SELECT id, ' . student_name_sql() . ' AS name, COALESCE(register_no, student_uid, register_number) AS register_no, class_name FROM students WHERE class_name = :class_name ORDER BY register_no ASC LIMIT ' . $limit);
        $st->execute(['class_name' => $className]);
        return $st->fetchAll() ?: [];
    }
    $st = $db->query('SELECT id, ' . student_name_sql() . ' AS name, COALESCE(register_no, student_uid, register_number) AS register_no, class_name FROM students ORDER BY class_name ASC LIMIT ' . $limit);
    return $st ? ($st->fetchAll() ?: []) : [];
}

function photo_upload_path(string $registerNo): string {
    return __DIR__ . '/photos/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $registerNo) . '.jpg';
}

function fetch_student_result(PDO $db, array $appConfig, int $studentId, int $examId = 0): ?array {
    $studentStmt = $db->prepare('SELECT id, COALESCE(register_no, student_uid, register_number) AS register_no, ' . student_name_sql() . ' AS name, class_name FROM students WHERE id = :id LIMIT 1');
    $studentStmt->execute(['id' => $studentId]);
    $student = $studentStmt->fetch();
    if (!$student) return null;

    $subjectStmt = $db->prepare('SELECT s.subject_name AS name, s.max_mark, s.pass_mark, COALESCE(m.mark, 0) AS mark FROM subjects s LEFT JOIN marks m ON m.subject_id = s.id AND m.student_id = :student_id AND m.exam_id = :exam_id ORDER BY s.display_order ASC, s.id ASC');
    $subjectStmt->execute(['student_id' => $studentId, 'exam_id' => $examId]);
    $subjects = $subjectStmt->fetchAll() ?: [];
    if (!$subjects) return null;

    $obtainedTotal = 0.0;
    $possibleTotal = 0.0;
    $allPassed = true;
    foreach ($subjects as &$subject) {
        $mark = (float)($subject['mark'] ?? 0);
        $maxMark = (float)($subject['max_mark'] ?? ($appConfig['default_subject_max_mark'] ?? 50));
        $passMark = (float)($subject['pass_mark'] ?? ($appConfig['default_subject_pass_mark'] ?? 18));
        $subject['mark'] = $mark;
        $subject['max_mark'] = $maxMark;
        $subject['status'] = $mark >= $passMark ? 'PASS' : 'FAIL';
        if ($mark < $passMark) $allPassed = false;
        $obtainedTotal += $mark;
        $possibleTotal += $maxMark;
    }
    unset($subject);

    $percentage = $possibleTotal > 0 ? round(($obtainedTotal / $possibleTotal) * 100, 2) : 0.0;
    $grade = $percentage >= 90 ? 'A+' : ($percentage >= 80 ? 'A' : ($percentage >= 70 ? 'B+' : ($percentage >= 60 ? 'B' : ($percentage >= 50 ? 'C' : 'F'))));

    return [
        'student' => $student,
        'subjects' => $subjects,
        'obtained_total' => round($obtainedTotal, 2),
        'possible_total' => round($possibleTotal, 2),
        'percentage' => $percentage,
        'grade' => $grade,
        'status' => $allPassed ? 'PASS' : 'FAIL',
        'class_rank' => 'N/A',
        'overall_rank' => 'N/A',
        'promotion_message' => $allPassed ? 'Promoted to next class.' : 'Needs improvement for promotion.',
    ];
}

function render_header(string $title): void {
    $active = basename((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . '</title>';
    echo '<style>:root{--bg:#0f172a;--glass:rgba(255,255,255,.14);--line:rgba(255,255,255,.24);--text:#f8fafc;--muted:#cbd5e1}*{box-sizing:border-box}body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;color:var(--text);background:linear-gradient(135deg,#0b1023,#16213e,#1d2f5f)}.container{max-width:1180px;margin:16px auto;padding:0 12px 20px}.hero,.card{padding:14px;border-radius:14px;background:var(--glass);border:1px solid var(--line);backdrop-filter:blur(10px)}.card{margin-top:12px}.nav{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}.nav a{padding:8px 12px;border-radius:8px;background:rgba(124,58,237,.35);border:1px solid rgba(196,181,253,.4);text-decoration:none;color:#fff}.nav a.active{outline:2px solid #c4b5fd}input,select,button,textarea{width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,.3);background:rgba(15,23,42,.6);color:#fff}button{cursor:pointer;background:linear-gradient(120deg,#4f46e5,#7c3aed)}table{width:100%;border-collapse:collapse;min-width:560px}th,td{padding:9px;border-bottom:1px solid rgba(255,255,255,.18);text-align:left}.small{color:var(--muted)}.msg-ok{color:#86efac}.msg-bad{color:#fecaca}</style></head><body><main class="container">';
    echo '<section class="hero"><h1>' . e($title) . '</h1><p class="small">Connected Madrasa Portal Pages</p><nav class="nav">';
    $menu = ['index.php'=>'Portal','admin_bulk_upload.php'=>'Bulk Import','student_result_viewer.php'=>'Result Viewer','pages/dashboard.php'=>'ERP Dashboard','pages/user_guide.php'=>'User Guide','pages/fest_dashboard.php'=>'Fest Dashboard','pages/festivals.php'=>'Festivals','pages/festival_events.php'=>'Fest Events','pages/festival_participants.php'=>'Fest Participants','pages/festival_scoreboard.php'=>'Fest Scoreboard','pages/students.php'=>'Students','pages/subjects.php'=>'Subjects','pages/exams.php'=>'Exams','pages/results_import.php'=>'Results Import','pages/rank_list.php'=>'Rank List','pages/marksheet.php'=>'Marksheet','pages/attendance_qr.php'=>'Attendance QR','pages/fees.php'=>'Fees','pages/id_card.php'=>'ID Cards','reports/result_report.php'=>'Reports','admission_form.php'=>'Online Admission','fee_management.php'=>'Fee Management','id_card_generator_bulk.php'=>'ID Bulk','idcard_edit.php'=>'Edit ID Card','idcard.php'=>'ID Card','self_card.php'=>'Self Card','self_card_bulk.php'=>'Self Card Bulk','attendance_qr.php'=>'QR Attendance','qr_scanner_dashboard.php'=>'Scanner Dashboard','scanner.php'=>'Camera Scanner','database_select.php'=>'DB Select','database_mysql.php'=>'MySQL DB','database_sqlite.php'=>'SQLite DB','admin_portal.php'=>'Admin Portal','class_result_sheet.php'=>'Class Sheet','register_result.php'=>'Register Result','class_result.php'=>'Class Result','certificate.php'=>'Certificate','admin_cards.php'=>'ID Admin','idcard_bulk.php'=>'A4 ID Print'];
    foreach ($menu as $href => $label) echo '<a class="' . ($active === $href ? 'active' : '') . '" href="' . e($href) . '">' . e($label) . '</a>';
    echo '</nav></section>';
}

function render_footer(): void { echo '</main></body></html>'; }

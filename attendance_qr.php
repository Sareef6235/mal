<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$db = db_connect($config['db']);
if (!$db instanceof PDO) {
    die('Database connection failed');
}

$message = '';
$error = '';

$driver = (string)$db->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'sqlite') {
    $db->exec('CREATE TABLE IF NOT EXISTS attendance_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, profile_id INTEGER NOT NULL, name TEXT NOT NULL, msr_no TEXT NOT NULL, phone TEXT NOT NULL, place TEXT NOT NULL, attended_at TEXT DEFAULT CURRENT_TIMESTAMP)');
} else {
    try {
        $db->exec('CREATE TABLE IF NOT EXISTS attendance_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            profile_id INT NOT NULL,
            name VARCHAR(191) NOT NULL,
            msr_no VARCHAR(100) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            place VARCHAR(191) NOT NULL,
            attended_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX(msr_no),
            INDEX(profile_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    } catch (Throwable) {}
}

function normalize_scanned_qr_token(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') return '';

    if (filter_var($raw, FILTER_VALIDATE_URL)) {
        $q = parse_url($raw, PHP_URL_QUERY);
        if (is_string($q)) {
            parse_str($q, $params);
            return trim((string) ($params['self_qr'] ?? ''));
        }
    }

    if (str_starts_with(strtoupper($raw), 'SELF_QR:')) return trim(substr($raw, 8));
    return $raw;
}

function ensure_missing_qr_tokens(PDO $db): void
{
    $st = $db->query("SELECT id FROM self_profiles WHERE qr_token IS NULL OR qr_token=''");
    $rows = $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    if (!$rows) return;

    $up = $db->prepare('UPDATE self_profiles SET qr_token=:t WHERE id=:id');
    foreach ($rows as $r) $up->execute(['t' => bin2hex(random_bytes(12)), 'id' => (int) $r['id']]);
}

function mark_attendance_by_msr(PDO $db, string $msr): array
{
    $msr = trim($msr);
    if ($msr === '') return [false, 'MSR required.'];

    $st = $db->prepare('SELECT id, name, msr_no, phone, place FROM self_profiles WHERE msr_no=:msr LIMIT 1');
    $st->execute(['msr' => $msr]);
    $p = $st->fetch(PDO::FETCH_ASSOC);
    if (!$p) return [false, 'MSR Not Found'];

    $driver = (string)$db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $chk = $db->prepare('SELECT id FROM attendance_logs WHERE msr_no=:msr AND date(attended_at)=date("now") LIMIT 1');
    } else {
        $chk = $db->prepare('SELECT id FROM attendance_logs WHERE msr_no=:msr AND DATE(attended_at)=CURDATE() LIMIT 1');
    }
    $chk->execute(['msr' => (string) $p['msr_no']]);
    if ($chk->fetchColumn()) return [true, 'Already Marked Today'];

    $ins = $db->prepare('INSERT INTO attendance_logs(profile_id, name, msr_no, phone, place) VALUES(:pid,:name,:msr,:phone,:place)');
    $ins->execute(['pid'=>(int)$p['id'],'name'=>(string)$p['name'],'msr'=>(string)$p['msr_no'],'phone'=>(string)$p['phone'],'place'=>(string)$p['place']]);

    return [true, 'Attendance Marked\nName : ' . (string)$p['name'] . '\nMSR : ' . (string)$p['msr_no'] . '\nPhone : ' . (string)$p['phone'] . '\nPlace : ' . (string)$p['place']];
}

function mark_attendance_by_token(PDO $db, string $token): array
{
    $token = normalize_scanned_qr_token($token);
    if ($token === '') return [false, 'QR token required.'];

    $st = $db->prepare('SELECT id, name, msr_no, phone, place FROM self_profiles WHERE qr_token=:t LIMIT 1');
    $st->execute(['t' => $token]);
    $p = $st->fetch();
    if (!$p) return [false, 'Profile not found for QR token. Please verify QR URL/token and self_profiles.qr_token values.'];

    $ins = $db->prepare('INSERT INTO attendance_logs(profile_id, name, msr_no, phone, place) VALUES(:pid,:name,:msr,:phone,:place)');
    $ins->execute(['pid'=>(int)$p['id'],'name'=>(string)$p['name'],'msr'=>(string)$p['msr_no'],'phone'=>(string)$p['phone'],'place'=>(string)$p['place']]);
    return [true, 'Attendance marked for ' . (string) $p['name'] . ' (' . (string) $p['msr_no'] . ')'];
}

if (isset($_GET['scan'])) {
    [$ok, $msg] = mark_attendance_by_msr($db, (string) ($_GET['scan'] ?? ''));
    $color = $ok ? 'green' : 'red';
    header('Content-Type: text/html; charset=utf-8');
    echo '<h2 style="color:' . $color . '">' . e($ok ? 'Attendance Marked' : 'MSR Not Found') . '</h2>';
    echo '<pre style="font-family:Arial;white-space:pre-wrap">' . e($msg) . '</pre>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $msg] = mark_attendance_by_token($db, (string) ($_POST['self_qr'] ?? ''));
    if ($ok) $message = $msg; else $error = $msg;
}

if (isset($_GET['self_qr'])) {
    [$ok, $msg] = mark_attendance_by_token($db, (string) ($_GET['self_qr'] ?? ''));
    if ($ok) $message = $msg; else $error = $msg;
}

$showLogs = isset($_GET['show_logs']) && $_GET['show_logs'] === '1';
$logs = [];
if ($showLogs) {
    try {
        $lg = $db->query('SELECT msr_no, name, phone, place, attended_at FROM attendance_logs ORDER BY id DESC LIMIT 200');
        $logs = $lg ? ($lg->fetchAll() ?: []) : [];
    } catch (Throwable) { $logs = []; }
}

ensure_missing_qr_tokens($db);

$profiles = [];
try {
    $ps = $db->query('SELECT name, msr_no, qr_token FROM self_profiles ORDER BY id DESC LIMIT 100');
    $profiles = $ps ? ($ps->fetchAll() ?: []) : [];
} catch (Throwable) { $profiles = []; }

$baseUrl = attendance_base_url();

$profileCount = 0;
$emptyTokenCount = 0;
try {
    $c1 = $db->query('SELECT COUNT(*) FROM self_profiles');
    $profileCount = $c1 ? (int) $c1->fetchColumn() : 0;
    $c2 = $db->query("SELECT COUNT(*) FROM self_profiles WHERE qr_token IS NULL OR qr_token=''");
    $emptyTokenCount = $c2 ? (int) $c2->fetchColumn() : 0;
} catch (Throwable) {}

$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
$looksLocal = $host === '' || str_contains($host, '127.0.0.1') || str_contains($host, 'localhost');

render_header('NFC / QR Attendance - Self Card');
?>
<style>
.inner-card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 8px 20px rgba(0,0,0,.08);margin:12px 0;color:#0f172a}
.gridx{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
.item{background:#f8fafc;border-radius:10px;padding:10px;text-align:center}.item img{width:110px;height:110px}
.msg{color:#166534}.err{color:#b91c1c}.warn{color:#92400e}
.table{width:100%;border-collapse:collapse}.table th,.table td{padding:8px;border-bottom:1px solid #e2e8f0;text-align:left;color:#0f172a}
.btn-link{display:inline-block;padding:8px 10px;background:#1d4ed8;color:#fff;border-radius:8px;text-decoration:none}
</style>
<div class="inner-card">
    <h2>NFC / QR Attendance (Self Card)</h2>
    <p>Self Card QR scan ചെയ്താൽ attendance നേരിട്ട് DB-യിൽ save ചെയ്യും.</p>
    <p>MSR direct scan URL: <code><?= e(attendance_base_url() . '?scan=451') ?></code></p>
    <p>Attendance URL: <code><?= e($baseUrl) ?></code></p>
    <?php if ($looksLocal): ?><p class="warn">⚠️ Mobile scan ന് https domain URL set ചെയ്യുക (example: https://yourdomain.com/qasw/attendance_qr.php) അല്ലെങ്കിൽ LAN IP ഉപയോഗിക്കുക.</p><?php endif; ?>
    <p>Profiles in DB: <b><?= e((string) $profileCount) ?></b> | Empty qr_token rows: <b><?= e((string) $emptyTokenCount) ?></b></p>
    <?php if ($message): ?><p class="msg"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
    <form method="post" style="display:flex;gap:10px;flex-wrap:wrap">
        <input name="self_qr" placeholder="Paste token or full QR URL" autofocus>
        <button type="submit">Mark Attendance</button>
        <a class="btn-link" href="attendance_qr.php?show_logs=1">Attendance DB Logs കാണുക</a>
    </form>
</div>

<?php if ($showLogs): ?>
<div class="inner-card">
    <h3>Recent Attendance Logs (DB)</h3>
    <table class="table">
        <thead><tr><th>പേര്</th><th>MSR No</th><th>Phone</th><th>Place</th><th>Time</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= e((string) ($log['name'] ?? '-')) ?></td>
                <td><?= e((string) ($log['msr_no'] ?? '')) ?></td>
                <td><?= e((string) ($log['phone'] ?? '-')) ?></td>
                <td><?= e((string) ($log['place'] ?? '-')) ?></td>
                <td><?= e((string) ($log['attended_at'] ?? '-')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="inner-card">
    <h3>Recent Self Profiles QR (Sample 100)</h3>
    <div class="gridx">
        <?php foreach ($profiles as $p):
            $scanData = $baseUrl . '?scan=' . urlencode((string) $p['msr_no']);
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=' . rawurlencode($scanData);
        ?>
        <div class="item">
            <img src="<?= e($qrUrl) ?>" alt="QR">
            <div><b><?= e((string) $p['msr_no']) ?></b></div>
            <div><?= e((string) $p['name']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php render_footer(); ?>

<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/bootstrap.php';

$db = db_connect($config['db']);
if (!$db instanceof PDO) die('Database connection failed');

$driver = (string)$db->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'sqlite') {
    $db->exec('CREATE TABLE IF NOT EXISTS attendance_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, profile_id INTEGER NOT NULL, name TEXT NOT NULL, msr_no TEXT NOT NULL, phone TEXT NOT NULL, place TEXT NOT NULL, attended_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    $db->exec('CREATE TABLE IF NOT EXISTS self_profiles (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, msr_no TEXT NOT NULL UNIQUE, address TEXT NOT NULL, place TEXT NOT NULL, work_madrasa TEXT NOT NULL, phone TEXT NOT NULL, qr_token TEXT NOT NULL UNIQUE, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)');
} else {
    $db->exec('CREATE TABLE IF NOT EXISTS attendance_logs (id INT AUTO_INCREMENT PRIMARY KEY, profile_id INT NOT NULL, name VARCHAR(191) NOT NULL, msr_no VARCHAR(100) NOT NULL, phone VARCHAR(50) NOT NULL, place VARCHAR(191) NOT NULL, attended_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX(msr_no), INDEX(profile_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $db->exec('CREATE TABLE IF NOT EXISTS self_profiles (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(191) NOT NULL, msr_no VARCHAR(100) NOT NULL UNIQUE, address VARCHAR(255) NOT NULL, place VARCHAR(191) NOT NULL, work_madrasa VARCHAR(191) NOT NULL, phone VARCHAR(50) NOT NULL, qr_token VARCHAR(64) NOT NULL UNIQUE, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

if (!isset($_SESSION['self_cards']) || !is_array($_SESSION['self_cards'])) $_SESSION['self_cards'] = [];

$message=''; $error='';
$token = trim((string)($_GET['token'] ?? ''));
$action = trim((string)($_GET['action'] ?? ''));

if ($token !== '' && $action === '' && !isset($_SESSION['self_cards'][$token])) {
    $st = $db->prepare('SELECT id, name, msr_no, phone, place FROM self_profiles WHERE qr_token = :t LIMIT 1');
    $st->execute(['t' => $token]);
    $profile = $st->fetch();
    if ($profile) {
        $ins = $db->prepare('INSERT INTO attendance_logs(profile_id, name, msr_no, phone, place) VALUES(:pid, :name, :msr, :phone, :place)');
        $ins->execute(['pid'=>(int)$profile['id'],'name'=>(string)$profile['name'],'msr'=>(string)$profile['msr_no'],'phone'=>(string)$profile['phone'],'place'=>(string)$profile['place']]);
        $message = 'Attendance marked for ' . (string)$profile['name'] . ' (' . (string)$profile['msr_no'] . ')';
    } else $error = 'QR Profile not found';
}

$attendanceBase = attendance_base_url();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create_self_card'])) {
    $name=trim((string)($_POST['self_name']??'')); $msrNo=trim((string)($_POST['self_msr_no']??'')); $address=trim((string)($_POST['self_address']??'')); $residence=trim((string)($_POST['self_residence']??'')); $workMadrasa=trim((string)($_POST['self_work_madrasa']??'')); $phone=trim((string)($_POST['self_phone']??''));
    if ($name===''||$msrNo===''||$address===''||$residence===''||$workMadrasa===''||$phone==='') $error='Please fill all required fields.';
    elseif (!isset($_FILES['self_photo'])) $error='Photo is required.';
    elseif ((int)($_FILES['self_photo']['size'] ?? 0) > 500*1024) $error='Photo must be 500KB or less.';
    else {
        [$okUpload,$uploadMessage] = validate_image_upload($_FILES['self_photo'], 500*1024);
        if (!$okUpload) $error = $uploadMessage;
        else {
            $tmpDir = __DIR__ . '/tmp_self_cards'; if (!is_dir($tmpDir)) mkdir($tmpDir, 0775, true);
            $token = bin2hex(random_bytes(16)); $photoPath = $tmpDir . '/' . $token . '.jpg';
            if (!move_uploaded_file((string)$_FILES['self_photo']['tmp_name'], $photoPath)) $error = 'Unable to save temporary photo.';
            else {
                $qrToken = bin2hex(random_bytes(12));
                if ($driver === 'sqlite') {
                    $up = $db->prepare('INSERT INTO self_profiles(name, msr_no, address, place, work_madrasa, phone, qr_token) VALUES(:name,:msr,:address,:place,:work,:phone,:qr) ON CONFLICT(msr_no) DO UPDATE SET name=:name,address=:address,place=:place,work_madrasa=:work,phone=:phone,qr_token=:qr');
                } else {
                    $up = $db->prepare('INSERT INTO self_profiles(name, msr_no, address, place, work_madrasa, phone, qr_token) VALUES(:name,:msr,:address,:place,:work,:phone,:qr) ON DUPLICATE KEY UPDATE name=:name,address=:address,place=:place,work_madrasa=:work,phone=:phone,qr_token=:qr');
                }
                $up->execute(['name'=>$name,'msr'=>$msrNo,'address'=>$address,'place'=>$residence,'work'=>$workMadrasa,'phone'=>$phone,'qr'=>$qrToken]);
                $scanUrl = attendance_scan_msr_url($msrNo);
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . rawurlencode($scanUrl);
                $_SESSION['self_cards'][$token] = ['name'=>$name,'msr_no'=>$msrNo,'address'=>$address,'residence'=>$residence,'work_madrasa'=>$workMadrasa,'phone'=>$phone,'photo'=>$photoPath,'qr_url'=>$qrUrl,'qr_token'=>$qrToken,'created_at'=>time()];
                $message = 'Self card ready. QR code auto generated for attendance scan.';
            }
        }
    }
}

if ($token !== '' && isset($_SESSION['self_cards'][$token]) && in_array($action, ['view','download'], true)) {
    $card = $_SESSION['self_cards'][$token]; $photoData='';
    if (is_file((string)$card['photo'])) { $raw = file_get_contents((string)$card['photo']); if ($raw !== false) $photoData = 'data:image/jpeg;base64,' . base64_encode($raw); }
    if ($action === 'download') { header('Content-Type: text/html; charset=utf-8'); header('Content-Disposition: attachment; filename="self-card-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$card['msr_no']) . '.html"'); }

    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Self Card</title><style>body{font-family:Arial,sans-serif;background:#e2e8f0;padding:20px;margin:0}.card{max-width:440px;width:100%;margin:auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 15px 40px rgba(0,0,0,.2)}.header{background:#1e40af;color:#fff;padding:14px;text-align:center;font-weight:700;font-size:18px}.photo{text-align:center;margin-top:15px}.photo img{width:110px;height:130px;object-fit:cover;border-radius:8px;border:3px solid #ddd}.info{padding:16px;font-size:14px;line-height:1.45}.info p{margin:6px 0;word-break:break-word}.qr{text-align:center;padding:0 0 14px}.qr img{width:120px;height:120px}.footer{text-align:center;font-size:12px;background:#f8fafc;padding:10px;color:#334155}@media (max-width:640px){body{padding:12px}.header{font-size:16px}.info{font-size:13px}}</style></head><body><div class="card"><div class="header">NIM MADRASA SELF ID CARD</div><div class="photo"><img src="' . e($photoData !== '' ? $photoData : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==') . '" alt="Photo"></div><div class="info"><p><b>പേര്:</b> ' . e((string)$card['name']) . '</p><p><b>MSR No:</b> ' . e((string)$card['msr_no']) . '</p><p><b>Address:</b> ' . e((string)$card['address']) . '</p><p><b>താമസിക്കുന്ന സ്ഥലം:</b> ' . e((string)$card['residence']) . '</p><p><b>ജോലി ചെയ്യുന്ന മദ്രസ:</b> ' . e((string)$card['work_madrasa']) . '</p><p><b>ഫോണ് നമ്പര്:</b> ' . e((string)$card['phone']) . '</p></div><div class="qr"><img src="' . e((string)$card['qr_url']) . '" alt="QR"><div>Scan for attendance</div></div><div class="footer">Official Self Identity Card</div></div></body></html>';
    if ($action === 'download') { if (is_file((string)$card['photo'])) @unlink((string)$card['photo']); unset($_SESSION['self_cards'][$token]); }
    exit;
}

render_header('Self Card Create / Download (Temporary)');
?>
<style>
.inner-card{background:#fff;border-radius:14px;padding:18px;box-shadow:0 10px 24px rgba(2,6,23,.12);margin:12px 0;color:#0f172a}
.rowx{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.rowx .full{grid-column:1 / -1}
.rowx input,.rowx button{width:100%;padding:10px;border-radius:8px;border:1px solid #cbd5e1;font-size:14px;background:#fff;color:#0f172a}
.rowx button{background:#1d4ed8;color:#fff;border:none;font-weight:600;cursor:pointer}
.btn-link{display:inline-block;padding:9px 11px;background:#1d4ed8;color:#fff;border-radius:8px;text-decoration:none;margin-right:8px;margin-top:8px}
.msg{color:#166534}.err{color:#b91c1c}
@media (max-width:768px){.rowx{grid-template-columns:1fr}}
</style>
<div class="inner-card">
    <h3>Self Card Create / Download (No DB Save)</h3>
    <p class="small" style="color:#334155">Photo must be 500KB or less. QR auto-generated for attendance.</p>
    <p class="small" style="color:#475569">Attendance URL: <code><?= e($attendanceBase) ?></code></p>
    <?php if ($message !== ''): ?><p class="msg"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="rowx">
        <input name="self_name" placeholder="പേര്" required>
        <input name="self_msr_no" placeholder="MSR No" required>
        <input class="full" name="self_address" placeholder="address" required>
        <input name="self_residence" placeholder="താമസിക്കുന്ന സ്ഥലം" required>
        <input name="self_work_madrasa" placeholder="ജോലി ചെയ്യുന്ന മദ്രസ" required>
        <input name="self_phone" placeholder="ഫോണ് നമ്പര്" required>
        <input class="full" type="file" name="self_photo" accept="image/*" required>
        <button class="full" name="create_self_card" value="1" type="submit">Create Temporary Card</button>
    </form>
    <a class="btn-link" href="id_card_generator_bulk.php">Bulk ID Card Page</a>
    <a class="btn-link" href="attendance_qr.php?show_logs=1">Attendance DB Logs കാണുക</a>
    <?php if ($token !== '' && isset($_SESSION['self_cards'][$token])): ?>
        <p style="margin-top:10px">
            <a class="btn-link" href="self_card.php?action=view&token=<?= e($token) ?>" target="_blank">Preview</a>
            <a class="btn-link" href="self_card.php?action=download&token=<?= e($token) ?>" target="_blank">Download & Auto Remove</a>
        </p>
    <?php endif; ?>
</div>
<?php render_footer(); ?>

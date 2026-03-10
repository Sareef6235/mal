<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/bootstrap.php';
$db = db_connect($config['db']);
if (!$db instanceof PDO) die('Database connection failed');

$driver = db_driver($db);
try {
    if ($driver === 'sqlite') {
        $db->exec('CREATE TABLE IF NOT EXISTS self_profiles (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, msr_no TEXT NOT NULL UNIQUE, address TEXT NOT NULL, place TEXT NOT NULL, work_madrasa TEXT NOT NULL, phone TEXT NOT NULL, qr_token TEXT NOT NULL UNIQUE, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    } else {
        $db->exec('CREATE TABLE IF NOT EXISTS self_profiles (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(191) NOT NULL, msr_no VARCHAR(100) NOT NULL UNIQUE, address VARCHAR(255) NOT NULL, place VARCHAR(191) NOT NULL, work_madrasa VARCHAR(191) NOT NULL, phone VARCHAR(50) NOT NULL, qr_token VARCHAR(64) NOT NULL UNIQUE, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)');
    }
} catch (Throwable) {}

$message=''; $error=''; $created=[];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_bulk'])) {
        $rowsRaw = trim((string)($_POST['bulk_rows'] ?? ''));
        if ($rowsRaw === '') $error = 'Bulk rows required.';
        else {
            $lines = preg_split('/\r\n|\r|\n/', $rowsRaw) ?: [];
            $sql = $driver==='sqlite'
                ? 'INSERT INTO self_profiles(name, msr_no, address, place, work_madrasa, phone, qr_token) VALUES(:name,:msr,:address,:place,:work,:phone,:qr) ON CONFLICT(msr_no) DO UPDATE SET name=:name,address=:address,place=:place,work_madrasa=:work,phone=:phone,qr_token=:qr'
                : 'INSERT INTO self_profiles(name, msr_no, address, place, work_madrasa, phone, qr_token) VALUES(:name,:msr,:address,:place,:work,:phone,:qr) ON DUPLICATE KEY UPDATE name=:name,address=:address,place=:place,work_madrasa=:work,phone=:phone,qr_token=:qr';
            $up = $db->prepare($sql);
            foreach ($lines as $line) {
                $line=trim($line); if ($line==='') continue;
                $parts = str_getcsv($line); if (count($parts) < 6) continue;
                [$name,$msr,$address,$place,$work,$phone] = array_map(fn($v)=>trim((string)$v), array_slice($parts,0,6));
                if (!$name||!$msr||!$address||!$place||!$work||!$phone) continue;
                $qrToken = bin2hex(random_bytes(12));
                $up->execute(['name'=>$name,'msr'=>$msr,'address'=>$address,'place'=>$place,'work'=>$work,'phone'=>$phone,'qr'=>$qrToken]);
                $created[]=['name'=>$name,'msr_no'=>$msr,'qr_token'=>$qrToken];
            }
            $message = count($created) . ' self cards/profiles prepared with unique QR.';
        }
    }
    if (isset($_POST['update_one'])) {
        $id=(int)($_POST['id']??0);
        if ($id>0) {
            $st=$db->prepare('UPDATE self_profiles SET name=:n,address=:a,place=:p,work_madrasa=:w,phone=:ph WHERE id=:id');
            $st->execute(['id'=>$id,'n'=>trim((string)$_POST['name']),'a'=>trim((string)$_POST['address']),'p'=>trim((string)$_POST['place']),'w'=>trim((string)$_POST['work_madrasa']),'ph'=>trim((string)$_POST['phone'])]);
            $message='Profile updated.';
        }
    }
}
$attendanceBase = attendance_base_url();
$rows = $db->query('SELECT * FROM self_profiles ORDER BY id DESC LIMIT 500')->fetchAll() ?: [];
render_header('Self Card Bulk Create');
?>
<style>.inner-card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 8px 20px rgba(0,0,0,.08);margin:12px 0;color:#0f172a}textarea,input,button{background:#fff !important;color:#0f172a !important;border:1px solid #cbd5e1 !important}.gridx{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}.item{background:#f8fafc;border-radius:10px;padding:10px;text-align:center}.item img{width:110px;height:110px}.msg{color:#166534}.err{color:#b91c1c}.edit-grid{display:grid;grid-template-columns:repeat(6,minmax(120px,1fr)) auto;gap:6px}@media(max-width:980px){.edit-grid{grid-template-columns:1fr}}</style>
<div class="inner-card"><h2>Bulk Self Card / QR Create</h2><p>CSV format per line: <code>name,msr_no,address,place,work_madrasa,phone</code></p><p>Attendance URL: <code><?= e($attendanceBase) ?></code></p><?php if ($message): ?><p class="msg"><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?><form method="post"><textarea name="bulk_rows" rows="8" placeholder="Ahammed,MSR1001,Main Road,Kozhikode,Noor Madrasa,9876543210"></textarea><button style="margin-top:8px" type="submit" name="create_bulk" value="1">Create Bulk Profiles + QR</button></form></div>
<?php if ($created): ?><div class="inner-card"><h3>Created Cards (Current Request)</h3><div class="gridx"><?php foreach ($created as $c): $scan=attendance_scan_msr_url((string)$c['msr_no']); $qr='https://api.qrserver.com/v1/create-qr-code/?size=110x110&data='.rawurlencode($scan); ?><div class="item"><img src="<?= e($qr) ?>" alt="QR"><div><b><?= e((string)$c['msr_no']) ?></b></div><div><?= e((string)$c['name']) ?></div></div><?php endforeach; ?></div></div><?php endif; ?>
<div class="inner-card"><p class="small">Total self profiles: <?= count($rows) ?></p><button onclick="window.print()" type="button">Print Bulk Cards</button></div>
<div class="inner-card"><div class="gridx"><?php foreach($rows as $r): $qr='https://api.qrserver.com/v1/create-qr-code/?size=110x110&data='.rawurlencode(attendance_scan_msr_url((string)$r['msr_no'])); ?><article class="item"><h4 style="margin:0 0 6px">NIM SELF CARD</h4><div><b><?= e((string)$r['name']) ?></b></div><div>MSR: <?= e((string)$r['msr_no']) ?></div><div><?= e((string)$r['work_madrasa']) ?></div><img src="<?= e($qr) ?>" width="80" height="80"></article><form method="post" class="edit-grid"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input name="name" value="<?= e((string)$r['name']) ?>"><input name="address" value="<?= e((string)$r['address']) ?>"><input name="place" value="<?= e((string)$r['place']) ?>"><input name="work_madrasa" value="<?= e((string)$r['work_madrasa']) ?>"><input name="phone" value="<?= e((string)$r['phone']) ?>"><button name="update_one" value="1">Update</button></form><?php endforeach; ?></div></div>
<?php render_footer();

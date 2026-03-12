<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$db = db_connect($config['db']);
if (!$db instanceof PDO) die('Database connection failed');
$reg = trim((string)($_GET['register_no'] ?? ''));
if ($reg === '') die('register_no required');
$st = $db->prepare('SELECT full_name AS name, class_name, register_no FROM students WHERE register_no=:r LIMIT 1');
$st->execute(['r'=>$reg]);
$s = $st->fetch();
if (!$s) die('Student not found');
$photo = is_file(photo_upload_path((string)$s['register_no'])) ? 'photos/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$s['register_no']) . '.jpg' : 'https://placehold.co/110x130';
$qr = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . rawurlencode(attendance_scan_msr_url((string)$s['register_no']));
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ID Card</title>
<style>body{font-family:Arial;background:#e5e7eb;padding:12px}.card{max-width:380px;margin:auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 10px 24px rgba(0,0,0,.16)}.h{background:#1d4ed8;color:#fff;text-align:center;padding:10px;font-weight:700}.b{padding:12px}.p{text-align:center}.p img{width:95px;height:120px;object-fit:cover;border-radius:8px;border:2px solid #e2e8f0}.qr{text-align:center;margin-top:8px}</style></head><body>
<div class="card"><div class="h">MADRASA STUDENT ID CARD</div><div class="b"><div class="p"><img src="<?= e($photo) ?>"></div><p><b>Name:</b> <?= e((string)$s['name']) ?></p><p><b>Register No:</b> <?= e((string)$s['register_no']) ?></p><p><b>Class:</b> <?= e((string)$s['class_name']) ?></p><div class="qr"><img src="<?= e($qr) ?>" width="100" height="100"><div>Attendance QR</div></div></div></div>
</body></html>

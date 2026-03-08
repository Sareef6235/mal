<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/bootstrap.php';
$db = db_connect($config['db']);
if (!$db instanceof PDO) die('Database connection failed');

$msg='';
$scan = trim((string)($_GET['scan'] ?? ''));
$selfQr = trim((string)($_GET['self_qr'] ?? ''));
if ($scan !== '') {
  $st=$db->prepare('SELECT id,name,msr_no,phone,place FROM self_profiles WHERE msr_no=:m LIMIT 1');
  $st->execute(['m'=>$scan]);
  if ($p=$st->fetch()) {
    $db->prepare('INSERT INTO attendance_logs(profile_id,name,msr_no,phone,place) VALUES(?,?,?,?,?)')->execute([$p['id'],$p['name'],$p['msr_no'],$p['phone'],$p['place']]);
    $msg='Attendance marked for '.$p['name'];
  } else $msg='MSR not found';
}
if ($selfQr !== '') {
  $st=$db->prepare('SELECT id,name,msr_no,phone,place FROM self_profiles WHERE qr_token=:t LIMIT 1');
  $st->execute(['t'=>$selfQr]);
  if ($p=$st->fetch()) {
    $db->prepare('INSERT INTO attendance_logs(profile_id,name,msr_no,phone,place) VALUES(?,?,?,?,?)')->execute([$p['id'],$p['name'],$p['msr_no'],$p['phone'],$p['place']]);
    $msg='Attendance marked for '.$p['name'];
  } else $msg='QR token not found';
}
$logs=[]; if(isset($_GET['show_logs'])) $logs=$db->query('SELECT * FROM attendance_logs ORDER BY id DESC LIMIT 300')->fetchAll();
render_header('QR Attendance');
?>
<div class="card">
<p class="small">Scan URL base: <code><?= e(attendance_base_url()) ?></code></p>
<?php if($msg): ?><p class="msg-ok"><?= e($msg) ?></p><?php endif; ?>
<form method="get" style="display:grid;grid-template-columns:1fr auto;gap:8px"><input name="scan" placeholder="Enter MSR to mark attendance"><button type="submit">Mark Attendance</button></form>
<p><a href="?show_logs=1">Show Logs</a></p>
</div>
<?php if($logs): ?><div class="card" style="overflow:auto"><table><thead><tr><th>ID</th><th>Name</th><th>MSR</th><th>Phone</th><th>Place</th><th>Attended At</th></tr></thead><tbody><?php foreach($logs as $l): ?><tr><td><?= (int)$l['id'] ?></td><td><?= e((string)$l['name']) ?></td><td><?= e((string)$l['msr_no']) ?></td><td><?= e((string)$l['phone']) ?></td><td><?= e((string)$l['place']) ?></td><td><?= e((string)$l['attended_at']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<?php render_footer();

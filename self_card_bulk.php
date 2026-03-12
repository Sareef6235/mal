<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/bootstrap.php';
$db = db_connect($config['db']);
if (!$db instanceof PDO) die('Database connection failed');
$rows = $db->query('SELECT * FROM self_profiles ORDER BY id DESC LIMIT 500')->fetchAll();
render_header('Self Card Bulk');
?>
<div class="card"><p class="small">Total self profiles: <?= count($rows) ?></p><button onclick="window.print()" type="button">Print Bulk Cards</button></div>
<div class="card"><div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px"><?php foreach($rows as $r): $qr='https://api.qrserver.com/v1/create-qr-code/?size=110x110&data='.rawurlencode(attendance_scan_msr_url((string)$r['msr_no'])); ?><article style="background:#fff;color:#111;border-radius:10px;padding:10px"><h4 style="margin:0 0 6px">NIM SELF CARD</h4><div><b><?= e((string)$r['name']) ?></b></div><div>MSR: <?= e((string)$r['msr_no']) ?></div><div><?= e((string)$r['work_madrasa']) ?></div><img src="<?= e($qr) ?>" width="80" height="80"></article><?php endforeach; ?></div></div>
<?php render_footer();

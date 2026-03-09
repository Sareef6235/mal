<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/bootstrap.php';
$db = db_connect($config['db']);
if (!$db instanceof PDO) die('Database connection failed');

$class = trim((string)($_GET['class_name'] ?? ''));
$students = fetch_students_for_id_cards($db, $class, 1000);
$classes = $db->query('SELECT DISTINCT class_name FROM students ORDER BY class_name')->fetchAll(PDO::FETCH_COLUMN) ?: [];

$madrasaTitle = setting($db, 'site_title', (string)($config['app']['site_title'] ?? 'Madrasa'));
render_header('Bulk ID Card Generator');
?>
<style>@media print { body * { visibility:hidden !important; } #printArea, #printArea * { visibility:visible !important; } #printArea { position:absolute; left:0; top:0; width:100%; } #printArea button { display:none !important; } }</style>
<div class="card">
  <form method="get" style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:end;">
    <select name="class_name"><option value="">All Classes</option><?php foreach($classes as $c): ?><option value="<?= e((string)$c) ?>" <?= $class===$c?'selected':'' ?>><?= e((string)$c) ?></option><?php endforeach; ?></select>
    <button type="submit">Load</button>
  </form>
  <p class="small">Total cards: <?= count($students) ?> | Use browser print (A4) for bulk output.</p>
</div>
<div class="card" id="printArea">
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;">
  <?php foreach($students as $s):
    $photo = is_file(photo_upload_path((string)$s['register_no'])) ? 'photos/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$s['register_no']) . '.jpg' : 'https://placehold.co/100x120';
    $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=' . rawurlencode(attendance_scan_msr_url((string)$s['register_no']));
  ?>
    <article style="background:#fff;color:#0f172a;border-radius:12px;padding:10px;border:1px solid #cbd5e1;">
      <h4 style="margin:0 0 6px;text-align:center;font-size:13px;"><?= e($madrasaTitle) ?> ID CARD</h4>
      <div style="display:flex;gap:8px;align-items:center;">
        <img src="<?= e($photo) ?>" alt="photo" style="width:70px;height:90px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
        <div style="font-size:12px;line-height:1.35;">
          <div><b><?= e((string)$s['name']) ?></b></div>
          <div>Reg: <?= e((string)$s['register_no']) ?></div>
          <div>Class: <?= e((string)$s['class_name']) ?></div>
        </div>
      </div>
      <div style="text-align:center;margin-top:6px;"><img src="<?= e($qr) ?>" alt="QR" width="70" height="70"><div style="font-size:11px;">Attendance QR</div></div>
    </article>
  <?php endforeach; ?>
  </div>
  <p style="margin-top:10px"><button onclick="window.print()" type="button">Print All</button></p>
</div>
<?php render_footer();

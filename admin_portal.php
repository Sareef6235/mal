<?php
declare(strict_types=1);
require_once __DIR__ . '/system.php';

$msg='';
$db = db();
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(isset($_POST['save_settings'])){
    sys_save_setting('google_sheet_csv_url', normalize_sheet_url(trim((string)$_POST['google_sheet_csv_url'])));
    sys_save_setting('principal_name', trim((string)$_POST['principal_name']));
    $msg='Settings updated.';
  }
  if(isset($_POST['sync_now'])){
    $count=sys_sync_from_google_sheet(sys_setting('google_sheet_csv_url'));
    $msg=$count>0?"Synced {$count} students.":'Sync failed or empty sheet.';
  }
}
$students=$db->query('SELECT id,COALESCE(register_no,student_uid) register_no,COALESCE(full_name,name) name,class_name FROM students ORDER BY class_name,name LIMIT 500')->fetchAll() ?: [];
$rows=sys_ranked_students();
$classes = $db->query('SELECT DISTINCT class_name FROM students ORDER BY class_name')->fetchAll(PDO::FETCH_COLUMN) ?: [];
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Portal</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>body{background:#f1f5f9;color:#1f2937}.card{border:0;border-radius:16px;box-shadow:0 10px 24px rgba(2,6,23,.08)}.menu a{margin:4px}.hero{background:linear-gradient(130deg,#2563eb,#10b981);color:#fff;border-radius:18px;padding:18px}.table{font-size:.93rem}</style></head><body>
<div class="container py-4">
<div class="hero mb-3"><h2 class="m-0">Madrasa Admin Portal</h2><div class="menu mt-2"><a class="btn btn-light btn-sm" href="index.php">Home</a><a class="btn btn-light btn-sm" href="admin_portal.php">Admin</a><a class="btn btn-light btn-sm" href="class_result_sheet.php">Class Result Sheet</a><a class="btn btn-light btn-sm" href="database_select.php">DB Select</a></div></div>
<?php if($msg): ?><div class="alert alert-info"><?= e($msg) ?></div><?php endif; ?>
<div class="row g-3">
<div class="col-lg-6"><div class="card p-3"><h5>Settings</h5><form method="post" class="row g-2"><input type="hidden" name="save_settings" value="1"><div class="col-12"><input class="form-control" name="google_sheet_csv_url" placeholder="Google Sheet CSV URL" value="<?= e(sys_setting('google_sheet_csv_url')) ?>"></div><div class="col-12"><input class="form-control" name="principal_name" placeholder="Principal Name" value="<?= e(sys_setting('principal_name','Principal')) ?>"></div><div class="col-12"><button class="btn btn-primary">Save Settings</button></div></form><form method="post" class="mt-2"><button class="btn btn-success" name="sync_now" value="1">Sync From Sheet</button></form></div></div>
<div class="col-lg-6"><div class="card p-3"><h5>Quick Student Certificate</h5><form method="get" action="certificate_download.php" class="row g-2"><div class="col-12"><select class="form-select" name="student_id" required><option value="">Select Student</option><?php foreach($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['register_no']) ?>)</option><?php endforeach; ?></select></div><div class="col-12"><button class="btn btn-primary">Download Certificate PDF</button></div></form></div></div>
</div>
<div class="card p-3 mt-3"><h5>Overall Rank List</h5><div class="table-responsive"><table class="table table-striped"><tr><th>Rank</th><th>Reg</th><th>Name</th><th>Class</th><th>Total</th></tr><?php foreach($rows as $r): ?><tr><td><?= e((string)$r['rank_position']) ?></td><td><?= e((string)$r['register_no']) ?></td><td><?= e((string)$r['name']) ?></td><td><?= e((string)$r['class_name']) ?></td><td><?= e((string)$r['total']) ?></td></tr><?php endforeach; ?></table></div></div>
<div class="card p-3 mt-3"><h5>Class Result Sheet</h5><form method="get" action="class_result_sheet.php" class="row g-2"><div class="col-lg-4"><select class="form-select" name="class" required><option value="">Select Class</option><?php foreach($classes as $c): ?><option value="<?= e((string)$c) ?>"><?= e((string)$c) ?></option><?php endforeach; ?></select></div><div class="col-lg-2"><button class="btn btn-secondary">Open</button></div></form></div>
</div></body></html>

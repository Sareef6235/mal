<?php
declare(strict_types=1);
require_once __DIR__ . '/../modules/festival.php';
$db = fest_db();
$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST' && fest_verify_csrf()) {
    $action = (string)($_POST['action'] ?? 'add');
    if ($action==='add') {
        $st=$db->prepare('INSERT INTO festivals(festival_name,festival_type,year,start_date,end_date,status,is_active) VALUES(:n,:t,:y,:s,:e,:st,0)');
        $st->execute(['n'=>trim((string)$_POST['festival_name']),'t'=>trim((string)$_POST['festival_type']),'y'=>(int)$_POST['year'],'s'=>trim((string)$_POST['start_date']),'e'=>trim((string)$_POST['end_date']),'st'=>trim((string)$_POST['status'])]);
        $msg='Festival created';
    } elseif ($action==='activate') {
        $id=(int)$_POST['id'];
        $db->beginTransaction();
        $db->exec('UPDATE festivals SET is_active=0');
        $st=$db->prepare('UPDATE festivals SET is_active=1,status="Active" WHERE id=:id');
        $st->execute(['id'=>$id]);
        $db->commit();
        $msg='Festival activated';
    } elseif ($action==='delete') {
        $st=$db->prepare('DELETE FROM festivals WHERE id=:id');
        $st->execute(['id'=>(int)$_POST['id']]);
        $msg='Festival deleted';
    }
}
$rows=$db->query('SELECT * FROM festivals ORDER BY year DESC,id DESC')->fetchAll()?:[];
include __DIR__ . '/../layout/header.php'; include __DIR__ . '/../layout/sidebar.php';
?>
<div class="card card-soft p-3">
  <h4>Festival Management</h4>
  <p class="text-success"><?= e($msg) ?></p>
  <form method="post" class="row g-2">
    <input type="hidden" name="csrf_token" value="<?= e(fest_csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <div class="col-md-3"><input class="form-control" name="festival_name" placeholder="Festival Name" required></div>
    <div class="col-md-2"><select class="form-control" name="festival_type"><option>Milad Fest</option><option>Art Fest</option><option>Annual Fest</option></select></div>
    <div class="col-md-1"><input class="form-control" name="year" type="number" value="<?= date('Y') ?>"></div>
    <div class="col-md-2"><input class="form-control" name="start_date" type="date"></div>
    <div class="col-md-2"><input class="form-control" name="end_date" type="date"></div>
    <div class="col-md-1"><select class="form-control" name="status"><option>Active</option><option>Completed</option></select></div>
    <div class="col-md-1"><button class="btn btn-primary w-100">Save</button></div>
  </form>
</div>
<div class="card card-soft p-3 mt-3">
<table class="table table-modern"><thead><tr><th>Name</th><th>Type</th><th>Year</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr>
<td><?= e((string)$r['festival_name']) ?> <?= (int)$r['is_active']===1?'<span class="badge bg-success">Active</span>':'' ?></td>
<td><?= e((string)$r['festival_type']) ?></td><td><?= (int)$r['year'] ?></td><td><?= e((string)$r['status']) ?></td>
<td class="d-flex gap-2">
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(fest_csrf_token()) ?>"><input type="hidden" name="action" value="activate"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-success">Activate</button></form>
<form method="post" onsubmit="return confirm('Delete festival?')"><input type="hidden" name="csrf_token" value="<?= e(fest_csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
</td>
</tr><?php endforeach; ?>
</tbody></table>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>

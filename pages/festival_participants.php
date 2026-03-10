<?php
declare(strict_types=1);
require_once __DIR__ . '/../modules/festival.php';
$db = fest_db();
$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST' && fest_verify_csrf()) {
    $qr = bin2hex(random_bytes(8));
    $st=$db->prepare('INSERT INTO festival_participants(participant_type,name,register_no,class_name,gender,event_id,category_id,house_id,qr_token) VALUES(:pt,:n,:r,:c,:g,:e,:cat,:h,:q)');
    $st->execute(['pt'=>trim((string)$_POST['participant_type']),'n'=>trim((string)$_POST['name']),'r'=>trim((string)$_POST['register_no']),'c'=>trim((string)$_POST['class_name']),'g'=>trim((string)$_POST['gender']),'e'=>(int)$_POST['event_id'],'cat'=>(int)$_POST['category_id'],'h'=>(int)$_POST['house_id'],'q'=>$qr]);
    $msg='Participant registered';
}
$events=$db->query('SELECT id,event_name FROM festival_events ORDER BY id DESC')->fetchAll()?:[];
$cats=$db->query('SELECT id,category_name FROM festival_categories ORDER BY category_name')->fetchAll()?:[];
$houses=$db->query('SELECT id,house_name FROM houses ORDER BY house_name')->fetchAll()?:[];
$rows=$db->query('SELECT p.*,e.event_name,c.category_name,h.house_name FROM festival_participants p JOIN festival_events e ON e.id=p.event_id JOIN festival_categories c ON c.id=p.category_id LEFT JOIN houses h ON h.id=p.house_id ORDER BY p.id DESC LIMIT 200')->fetchAll()?:[];
include __DIR__ . '/../layout/header.php'; include __DIR__ . '/../layout/sidebar.php';
?>
<div class="card card-soft p-3"><h4>Participant Registration</h4><p class="text-success"><?= e($msg) ?></p>
<form method="post" class="row g-2">
<input type="hidden" name="csrf_token" value="<?= e(fest_csrf_token()) ?>">
<div class="col-md-2"><select class="form-control" name="participant_type"><option value="student">Student</option><option value="external">External</option></select></div>
<div class="col-md-2"><input class="form-control" name="name" placeholder="Name" required></div>
<div class="col-md-1"><input class="form-control" name="register_no" placeholder="Reg"></div>
<div class="col-md-1"><input class="form-control" name="class_name" placeholder="Class"></div>
<div class="col-md-1"><select class="form-control" name="gender"><option>Boys</option><option>Girls</option><option>General</option></select></div>
<div class="col-md-2"><select class="form-control" name="event_id"><?php foreach($events as $e): ?><option value="<?= (int)$e['id'] ?>"><?= e((string)$e['event_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><select class="form-control" name="category_id"><?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e((string)$c['category_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><select class="form-control" name="house_id"><?php foreach($houses as $h): ?><option value="<?= (int)$h['id'] ?>"><?= e((string)$h['house_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
</form></div>
<div class="card card-soft p-3 mt-3"><table class="table table-modern"><thead><tr><th>Name</th><th>Type</th><th>Event</th><th>Category</th><th>House</th><th>QR</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= e((string)$r['name']) ?></td><td><?= e((string)$r['participant_type']) ?></td><td><?= e((string)$r['event_name']) ?></td><td><?= e((string)$r['category_name']) ?></td><td><?= e((string)($r['house_name'] ?? '-')) ?></td><td><code><?= e((string)$r['qr_token']) ?></code></td></tr><?php endforeach; ?></tbody></table></div>
<?php include __DIR__ . '/../layout/footer.php'; ?>

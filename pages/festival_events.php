<?php
declare(strict_types=1);
require_once __DIR__ . '/../modules/festival.php';
$db = fest_db();
$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST' && fest_verify_csrf()) {
    $st=$db->prepare('INSERT INTO festival_events(event_name,festival_id,category_id,max_score,event_date,venue) VALUES(:n,:f,:c,:m,:d,:v)');
    $st->execute(['n'=>trim((string)$_POST['event_name']),'f'=>(int)$_POST['festival_id'],'c'=>(int)$_POST['category_id'],'m'=>(float)$_POST['max_score'],'d'=>trim((string)$_POST['event_date']),'v'=>trim((string)$_POST['venue'])]);
    $msg='Event saved';
}
$festivals=$db->query('SELECT id,festival_name FROM festivals ORDER BY id DESC')->fetchAll()?:[];
$cats=$db->query('SELECT id,category_name FROM festival_categories ORDER BY category_name ASC')->fetchAll()?:[];
$rows=$db->query('SELECT e.*,f.festival_name,c.category_name FROM festival_events e JOIN festivals f ON f.id=e.festival_id JOIN festival_categories c ON c.id=e.category_id ORDER BY e.id DESC')->fetchAll()?:[];
include __DIR__ . '/../layout/header.php'; include __DIR__ . '/../layout/sidebar.php';
?>
<div class="card card-soft p-3"><h4>Event Management</h4><p class="text-success"><?= e($msg) ?></p>
<form method="post" class="row g-2">
<input type="hidden" name="csrf_token" value="<?= e(fest_csrf_token()) ?>">
<div class="col-md-3"><input class="form-control" name="event_name" placeholder="Event Name" required></div>
<div class="col-md-2"><select class="form-control" name="festival_id"><?php foreach($festivals as $f): ?><option value="<?= (int)$f['id'] ?>"><?= e((string)$f['festival_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select class="form-control" name="category_id"><?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e((string)$c['category_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><input class="form-control" name="max_score" value="100"></div>
<div class="col-md-2"><input class="form-control" name="event_date" type="date"></div>
<div class="col-md-1"><input class="form-control" name="venue" placeholder="Venue"></div>
<div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
</form></div>
<div class="card card-soft p-3 mt-3"><table class="table table-modern"><thead><tr><th>Event</th><th>Festival</th><th>Category</th><th>Max</th><th>Date</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= e((string)$r['event_name']) ?></td><td><?= e((string)$r['festival_name']) ?></td><td><?= e((string)$r['category_name']) ?></td><td><?= e((string)$r['max_score']) ?></td><td><?= e((string)$r['event_date']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php include __DIR__ . '/../layout/footer.php'; ?>

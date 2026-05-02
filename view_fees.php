<?php require_once 'config.php'; requireLogin();
$where='';$params=[];
if(!empty($_GET['class'])){$where=' WHERE f.class=?';$params[]=$_GET['class'];}
$stmt=$pdo->prepare("SELECT f.*, s.name student_name FROM fees f JOIN students s ON f.student_id=s.id $where ORDER BY f.date DESC, f.id DESC");
$stmt->execute($params);$fees=$stmt->fetchAll();
include 'header.php'; ?>
<div class="card glass p-3">
<h4>All Fee Entries</h4>
<form class="row g-2 mb-3"><div class="col-md-4"><input class="form-control" name="class" placeholder="Filter by class" value="<?= h($_GET['class'] ?? '') ?>"></div><div class="col-md-2"><button class="btn btn-outline-light">Filter</button></div></form>
<table class="table table-dark table-striped table-responsive">
<tr><th>ID</th><th>Student</th><th>Class</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th><th>Action</th></tr>
<?php foreach($fees as $f): ?><tr>
<td><?= (int)$f['id'] ?></td><td><?= h($f['student_name']) ?></td><td><?= h($f['class']) ?></td><td>₹<?= number_format((float)$f['amount'],2) ?></td><td><?= h($f['payment_method']) ?></td><td><?= h($f['date']) ?></td>
<td><span class="badge <?= $f['status']==='Verified'?'badge-verified':'badge-pending' ?>"><?= h($f['status']) ?></span></td>
<td><?php if(isAdmin() && $f['status']==='Pending'): ?><a class="btn btn-sm btn-success" href="verify.php?id=<?= (int)$f['id'] ?>">Verify</a><?php endif; ?></td>
</tr><?php endforeach; ?></table></div>
<?php include 'footer.php'; ?>

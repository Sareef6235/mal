<?php
require __DIR__ . '/../db.php';
$db = connect_db($config['db']);
ensure_core_tables($db);
$rows = $db->query('SELECT f.id,s.full_name,f.month_key,f.amount,f.paid_amount,f.status FROM fees f INNER JOIN students s ON s.id=f.student_id ORDER BY f.id DESC')->fetchAll() ?: [];
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="mb-0">Fees</h3><a class="btn btn-primary" href="fee_collect.php"><i class="bi bi-cash-stack me-1"></i>Collect Fee</a></div>
<div class="card card-soft p-3"><div class="mb-3"><input class="form-control" data-table-search="#feesTable" placeholder="Search fees..."></div><div class="table-responsive"><table class="table table-striped table-hover table-modern" id="feesTable"><thead><tr><th>Student</th><th>Month</th><th>Amount</th><th>Paid</th><th>Status</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= h((string)$r['full_name']) ?></td><td><?= h((string)$r['month_key']) ?></td><td><?= h((string)$r['amount']) ?></td><td><?= h((string)$r['paid_amount']) ?></td><td><span class="badge <?= strtolower((string)$r['status'])==='paid'?'text-bg-success':'text-bg-warning' ?>"><?= h((string)$r['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php include __DIR__ . '/../layout/footer.php';

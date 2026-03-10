<?php
require __DIR__ . '/../db.php';
$db = connect_db($config['db']);
ensure_core_tables($db);
$rows = $db->query('SELECT * FROM exams ORDER BY id DESC')->fetchAll() ?: [];
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Exams</h3>
  <a class="btn btn-primary" href="exam_add.php"><i class="bi bi-plus-circle me-1"></i>Add Exam</a>
</div>
<div class="card card-soft p-3">
  <div class="mb-3"><input class="form-control" data-table-search="#examTable" placeholder="Search exams..."></div>
  <div class="table-responsive"><table class="table table-striped table-hover table-modern" id="examTable"><thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Date</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= h((string)$r['exam_name']) ?></td><td><span class="badge text-bg-info"><?= h((string)$r['exam_type']) ?></span></td><td><?= h((string)$r['exam_date']) ?></td></tr><?php endforeach; ?></tbody></table></div>
</div>
<?php include __DIR__ . '/../layout/footer.php';

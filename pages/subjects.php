<?php
require __DIR__ . '/../db.php';
$db = connect_db($config['db']);
ensure_core_tables($db);
$rows = $db->query('SELECT * FROM subjects ORDER BY display_order,id')->fetchAll() ?: [];
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Subjects</h3>
  <a class="btn btn-primary" href="subject_add.php"><i class="bi bi-plus-circle me-1"></i>Add Subject</a>
</div>
<div class="card card-soft p-3">
  <div class="mb-3"><input class="form-control" data-table-search="#subjectTable" placeholder="Search subjects..."></div>
  <div class="table-responsive">
    <table class="table table-striped table-hover table-modern" id="subjectTable">
      <thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Max</th><th>Pass</th><th>Order</th></tr></thead>
      <tbody><?php foreach($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= h((string)$r['code']) ?></td><td><?= h((string)$r['subject_name']) ?></td><td><?= h((string)$r['max_mark']) ?></td><td><?= h((string)$r['pass_mark']) ?></td><td><?= h((string)$r['display_order']) ?></td></tr><?php endforeach; ?></tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../layout/footer.php';

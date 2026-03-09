<?php
require __DIR__ . '/../db.php';
$db = connect_db($config['db']);
ensure_core_tables($db);
$examId = (int)($_GET['exam_id'] ?? 0);
$ranks = $examId > 0 ? generate_rankings(calculate_student_totals($db, $examId)) : [];
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<h3 class="mb-3">Rank List</h3>
<div class="card card-soft p-3 mb-3">
  <form class="row g-2">
    <div class="col-md-4"><input class="form-control" name="exam_id" value="<?= $examId ?: '' ?>" placeholder="Enter exam id"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Generate</button></div>
  </form>
</div>
<div class="card card-soft p-3"><div class="table-responsive"><table class="table table-striped table-hover table-modern"><thead><tr><th>Rank</th><th>Student</th><th>Class</th><th>Total</th><th>Average</th></tr></thead><tbody><?php foreach($ranks as $r): ?><tr><td><span class="badge text-bg-warning"><?= (int)$r['rank'] ?></span></td><td><?= h((string)$r['full_name']) ?></td><td><?= h((string)$r['class_name']) ?></td><td><?= h((string)$r['total_marks']) ?></td><td><?= h((string)$r['average_marks']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php include __DIR__ . '/../layout/footer.php';

<?php require __DIR__.'/../db.php'; $db=connect_db($config['db']); ensure_core_tables($db); $examId=(int)($_GET['exam_id']??0); $rows=$examId>0?calculate_student_totals($db,$examId):[]; include __DIR__.'/../layout/header.php'; include __DIR__.'/../layout/sidebar.php'; ?>
<h3>Results View</h3>
<form class="row g-2 mb-2"><div class="col-md-4"><input class="form-control" name="exam_id" value="<?= $examId?:'' ?>" placeholder="Enter exam ID"></div><div class="col-md-2"><button class="btn btn-primary">Load</button></div></form>
<table class="table"><tr><th>Student</th><th>Class</th><th>Total</th><th>Average</th></tr><?php foreach($rows as $r): ?><tr><td><?= h((string)$r['full_name']) ?></td><td><?= h((string)$r['class_name']) ?></td><td><?= h((string)$r['total_marks']) ?></td><td><?= h((string)$r['average_marks']) ?></td></tr><?php endforeach; ?></table>
<?php include __DIR__.'/../layout/footer.php'; ?>

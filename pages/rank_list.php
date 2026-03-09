<?php require __DIR__.'/../db.php'; $db=connect_db($config['db']); ensure_core_tables($db); $examId=(int)($_GET['exam_id']??0); $ranks=$examId>0?generate_rankings(calculate_student_totals($db,$examId)):[]; include __DIR__.'/../layout/header.php'; include __DIR__.'/../layout/sidebar.php'; ?>
<h3>Rank List</h3>
<form class="row g-2 mb-2"><div class="col-md-4"><input class="form-control" name="exam_id" value="<?= $examId?:'' ?>" placeholder="Exam ID"></div><div class="col-md-2"><button class="btn btn-primary">Generate</button></div></form>
<table class="table"><tr><th>Rank</th><th>Student</th><th>Class</th><th>Total</th><th>Average</th></tr><?php foreach($ranks as $r): ?><tr><td><?= (int)$r['rank'] ?></td><td><?= h((string)$r['full_name']) ?></td><td><?= h((string)$r['class_name']) ?></td><td><?= h((string)$r['total_marks']) ?></td><td><?= h((string)$r['average_marks']) ?></td></tr><?php endforeach; ?></table>
<?php include __DIR__.'/../layout/footer.php'; ?>

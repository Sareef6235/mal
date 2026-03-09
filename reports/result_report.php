<?php require __DIR__.'/../db.php'; $db=connect_db($config['db']); ensure_core_tables($db); $examId=(int)($_GET['exam_id']??0); $rows=$examId?generate_rankings(calculate_student_totals($db,$examId)):[]; include __DIR__.'/../layout/header.php'; include __DIR__.'/../layout/sidebar.php'; ?>
<h3>Result Report</h3>
<form class="row g-2 mb-2"><div class="col-md-3"><input class="form-control" name="exam_id" placeholder="Exam ID" value="<?= $examId?:'' ?>"></div><div class="col-md-2"><button class="btn btn-primary">Load</button></div></form>
<table class="table"><tr><th>Rank</th><th>Name</th><th>Total</th><th>Average</th></tr><?php foreach($rows as $r): ?><tr><td><?= (int)$r['rank'] ?></td><td><?= h((string)$r['full_name']) ?></td><td><?= h((string)$r['total_marks']) ?></td><td><?= h((string)$r['average_marks']) ?></td></tr><?php endforeach; ?></table>
<?php include __DIR__.'/../layout/footer.php'; ?>

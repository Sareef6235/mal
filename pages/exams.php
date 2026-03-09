<?php require __DIR__.'/../db.php'; $db=connect_db($config['db']); ensure_core_tables($db); $rows=$db->query('SELECT * FROM exams ORDER BY id DESC')->fetchAll()?:[]; include __DIR__.'/../layout/header.php'; include __DIR__.'/../layout/sidebar.php'; ?>
<h3>Exams</h3><a class="btn btn-primary btn-sm" href="exam_add.php">Add Exam</a>
<table class="table mt-3"><tr><th>ID</th><th>Name</th><th>Type</th><th>Date</th></tr><?php foreach($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= h((string)$r['exam_name']) ?></td><td><?= h((string)$r['exam_type']) ?></td><td><?= h((string)$r['exam_date']) ?></td></tr><?php endforeach; ?></table>
<?php include __DIR__.'/../layout/footer.php'; ?>

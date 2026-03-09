<?php require __DIR__.'/../db.php'; $db=connect_db($config['db']); ensure_core_tables($db); $rows=$db->query('SELECT * FROM subjects ORDER BY display_order,id')->fetchAll()?:[]; include __DIR__.'/../layout/header.php'; include __DIR__.'/../layout/sidebar.php'; ?>
<h3>Subjects</h3><a class="btn btn-primary btn-sm" href="subject_add.php">Add Subject</a>
<table class="table mt-3"><tr><th>ID</th><th>Code</th><th>Name</th><th>Max</th><th>Pass</th></tr><?php foreach($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= h((string)$r['code']) ?></td><td><?= h((string)$r['subject_name']) ?></td><td><?= h((string)$r['max_mark']) ?></td><td><?= h((string)$r['pass_mark']) ?></td></tr><?php endforeach; ?></table>
<?php include __DIR__.'/../layout/footer.php'; ?>

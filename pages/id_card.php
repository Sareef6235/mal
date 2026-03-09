<?php require __DIR__.'/../db.php'; $db=connect_db($config['db']); ensure_core_tables($db); $rows=$db->query('SELECT register_no,full_name,class_name,photo_path FROM students ORDER BY id DESC LIMIT 100')->fetchAll()?:[]; include __DIR__.'/../layout/header.php'; include __DIR__.'/../layout/sidebar.php'; ?>
<h3>ID Card</h3>
<div class="row g-3"><?php foreach($rows as $r): ?><div class="col-md-4"><div class="card card-body"><h5><?= h((string)$r['full_name']) ?></h5><p>Reg: <?= h((string)$r['register_no']) ?></p><p>Class: <?= h((string)$r['class_name']) ?></p><p>QR: <code><?= h((string)$r['register_no']) ?></code></p></div></div><?php endforeach; ?></div>
<?php include __DIR__.'/../layout/footer.php'; ?>

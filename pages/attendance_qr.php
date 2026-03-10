<?php require __DIR__.'/../db.php'; $db=connect_db($config['db']); ensure_core_tables($db); $students=$db->query('SELECT id,register_no,full_name FROM students ORDER BY id DESC LIMIT 100')->fetchAll()?:[]; include __DIR__.'/../layout/header.php'; include __DIR__.'/../layout/sidebar.php'; ?>
<h3>Attendance QR</h3><p>Use register number as QR payload in this simplified module.</p>
<table class="table"><tr><th>Student</th><th>Register</th><th>QR Text</th></tr><?php foreach($students as $s): ?><tr><td><?= h((string)$s['full_name']) ?></td><td><?= h((string)$s['register_no']) ?></td><td><code><?= h((string)$s['register_no']) ?></code></td></tr><?php endforeach; ?></table>
<?php include __DIR__.'/../layout/footer.php'; ?>

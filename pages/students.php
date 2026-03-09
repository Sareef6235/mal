<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
$db = connect_db($config['db']); ensure_core_tables($db);
$rows = $db->query('SELECT id, register_no, full_name, class_name, phone FROM students ORDER BY id DESC')->fetchAll() ?: [];
include __DIR__ . '/../layout/header.php'; include __DIR__ . '/../layout/sidebar.php';
?>
<h3>Students</h3><a class="btn btn-primary btn-sm" href="student_add.php">Add Student</a>
<table class="table table-striped mt-3"><tr><th>ID</th><th>Register</th><th>Name</th><th>Class</th><th>Phone</th><th>Action</th></tr>
<?php foreach($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= h((string)$r['register_no']) ?></td><td><?= h((string)$r['full_name']) ?></td><td><?= h((string)$r['class_name']) ?></td><td><?= h((string)$r['phone']) ?></td><td><a href="student_edit.php?id=<?= (int)$r['id'] ?>">Edit</a></td></tr><?php endforeach; ?>
</table>
<?php include __DIR__ . '/../layout/footer.php';

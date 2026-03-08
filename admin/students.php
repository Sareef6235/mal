<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('admin');
$students = $pdo->query('SELECT id, name, register_no, class FROM students ORDER BY id DESC')->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Students</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><div class="container"><h1>Students</h1><a href="add_student.php">+ Add Student</a>
<table><tr><th>ID</th><th>Name</th><th>Reg No</th><th>Class</th></tr>
<?php foreach ($students as $s): ?><tr><td><?= $s['id'] ?></td><td><?= htmlspecialchars($s['name']) ?></td><td><?= htmlspecialchars($s['register_no']) ?></td><td><?= htmlspecialchars($s['class']) ?></td></tr><?php endforeach; ?>
</table><a href="dashboard.php">Back</a></div></body></html>

<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('teacher');
$students = $pdo->query('SELECT name, register_no, class FROM students ORDER BY name')->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Student List</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><?php require __DIR__ . '/../includes/header.php'; ?><div class="container"><h1>Student List</h1><table><tr><th>Name</th><th>Register No</th><th>Class</th></tr><?php foreach($students as $s):?><tr><td><?= htmlspecialchars($s['name'])?></td><td><?= htmlspecialchars($s['register_no'])?></td><td><?= htmlspecialchars($s['class'])?></td></tr><?php endforeach;?></table></div></body></html>

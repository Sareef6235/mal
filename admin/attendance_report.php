<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('admin');
$rows = $pdo->query('SELECT a.date,a.time,a.method,s.name,s.register_no FROM attendance a JOIN students s ON s.id=a.student_id ORDER BY a.id DESC LIMIT 100')->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Attendance Report</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><?php require __DIR__ . '/../includes/header.php'; ?><div class="container"><h1>Attendance Report</h1><table><tr><th>Date</th><th>Time</th><th>Name</th><th>Reg No</th><th>Method</th></tr><?php foreach($rows as $r):?><tr><td><?= $r['date']?></td><td><?= $r['time']?></td><td><?= htmlspecialchars($r['name'])?></td><td><?= htmlspecialchars($r['register_no'])?></td><td><?= htmlspecialchars($r['method'])?></td></tr><?php endforeach;?></table></div></body></html>

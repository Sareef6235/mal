<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('student');
$rows = $pdo->query('SELECT date,time,method FROM attendance ORDER BY id DESC LIMIT 30')->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Attendance History</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><?php require __DIR__ . '/../includes/header.php'; ?><div class="container"><h1>Attendance History</h1><table><tr><th>Date</th><th>Time</th><th>Method</th></tr><?php foreach($rows as $r):?><tr><td><?= $r['date']?></td><td><?= $r['time']?></td><td><?= htmlspecialchars($r['method'])?></td></tr><?php endforeach;?></table></div></body></html>

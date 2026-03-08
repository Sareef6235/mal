<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('admin');

$totalStudents = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$todayAttendance = (int) $pdo->query('SELECT COUNT(*) FROM attendance WHERE date = CURDATE()')->fetchColumn();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Admin Dashboard</title><link rel="stylesheet" href="../assets/css/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head>
<body><div class="container">
<h1>Admin Dashboard</h1>
<p>Total Students: <?= $totalStudents ?></p>
<p>Today's Attendance: <?= $todayAttendance ?></p>
<canvas id="statsChart" height="120"></canvas>
<nav><a href="students.php">Students</a> | <a href="teachers.php">Teachers</a> | <a href="attendance_report.php">Attendance Report</a> | <a href="../logout.php">Logout</a></nav>
</div>
<script>
new Chart(document.getElementById('statsChart'), {type:'bar', data:{labels:['Students','Today Attendance'], datasets:[{data:[<?= $totalStudents ?>, <?= $todayAttendance ?>], backgroundColor:['#4f46e5','#16a34a']}]}});
</script>
</body></html>

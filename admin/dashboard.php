<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('admin');

$totalStudents = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$todayAttendance = (int) $pdo->query('SELECT COUNT(*) FROM attendance WHERE date=CURDATE()')->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/header.php'; ?>
<div class="container card">
    <h2>Dashboard</h2>

    Total Students: <?= $totalStudents ?><br><br>
    Today's Attendance: <?= $todayAttendance ?>
</div>
</body>
</html>

<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('admin');

$totalStudents = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$todayAttendance = (int) $pdo->query('SELECT COUNT(*) FROM attendance WHERE date=CURDATE()')->fetchColumn();
$absentToday = max(0, $totalStudents - $todayAttendance);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <h2>Admin Dashboard</h2>
    <p>Realtime attendance summary and reports.</p>

    <div class="stat-grid">
        <div class="stat-box">
            <div class="label">Total Students</div>
            <div class="value"><?= $totalStudents ?></div>
        </div>
        <div class="stat-box">
            <div class="label">Today's Attendance</div>
            <div class="value"><?= $todayAttendance ?></div>
        </div>
        <div class="stat-box">
            <div class="label">Absent Today</div>
            <div class="value"><?= $absentToday ?></div>
        </div>
    </div>

    <div style="margin-top:16px; max-width:320px;">
        <a href="<?= htmlspecialchars(app_url('admin/export_excel.php')) ?>">
            <button type="button">Download Attendance Excel</button>
        </a>
    </div>
</div>
</body>
</html>

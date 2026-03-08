<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_auth('teacher');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <h1>Teacher Dashboard</h1>
    <div class="grid-2">
        <div class="stat-box">
            <div class="label">QR Attendance</div>
            <p>QR scanner ഉപയോഗിച്ച് attendance mark ചെയ്യാം.</p>
            <a href="<?= htmlspecialchars(app_url('teacher/take_attendance.php')) ?>"><button type="button">Open QR Scanner</button></a>
        </div>
        <div class="stat-box">
            <div class="label">Face Attendance</div>
            <p>Camera + face-api.js ഉപയോഗിച്ച് attendance mark ചെയ്യാം.</p>
            <a href="<?= htmlspecialchars(app_url('teacher/face_attendance.php')) ?>"><button type="button">Open Face Attendance</button></a>
        </div>
    </div>
</div>
</body>
</html>

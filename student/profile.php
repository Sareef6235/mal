<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_auth('student');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Student Profile</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><div class="container"><h1>Welcome <?= htmlspecialchars($_SESSION['user']['username']) ?></h1><p>Student profile module placeholder.</p><a href="attendance_history.php">Attendance History</a> | <a href="../logout.php">Logout</a></div></body></html>

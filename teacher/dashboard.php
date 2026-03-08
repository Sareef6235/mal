<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_auth('teacher');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Teacher Dashboard</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><div class="container"><h1>Teacher Dashboard</h1><nav><a href="take_attendance.php">Take Attendance</a> | <a href="student_list.php">Student List</a> | <a href="../logout.php">Logout</a></nav></div></body></html>

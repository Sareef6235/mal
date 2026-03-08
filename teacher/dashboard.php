<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_auth('teacher');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Teacher Dashboard</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><?php require __DIR__ . '/../includes/header.php'; ?><div class="container"><h1>Teacher Dashboard</h1><p>Attendance tools are ready.</p></div></body></html>

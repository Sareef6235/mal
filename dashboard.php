<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_auth();

$role = $_SESSION['user']['role'];
$target = [
    'admin' => 'admin/dashboard.php',
    'teacher' => 'teacher/dashboard.php',
    'student' => 'student/profile.php',
][$role] ?? 'login.php';

header('Location: ' . app_url($target));
exit;

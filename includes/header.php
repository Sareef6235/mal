<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';

$role = $_SESSION['user']['role'] ?? null;
?>
<header class="site-header">
    <div class="header-inner">
        <h2>🕌 Smart Madrasa Attendance</h2>
        <nav>
            <?php if ($role === 'admin'): ?>
                <a href="<?= htmlspecialchars(app_url('admin/dashboard.php')) ?>">Dashboard</a>
                <a href="<?= htmlspecialchars(app_url('admin/students.php')) ?>">Students</a>
                <a href="<?= htmlspecialchars(app_url('admin/attendance_report.php')) ?>">Report</a>
                <a href="<?= htmlspecialchars(app_url('admin/export_excel.php')) ?>">Excel Export</a>
            <?php elseif ($role === 'teacher'): ?>
                <a href="<?= htmlspecialchars(app_url('teacher/dashboard.php')) ?>">Dashboard</a>
                <a href="<?= htmlspecialchars(app_url('teacher/take_attendance.php')) ?>">QR Attendance</a>
                <a href="<?= htmlspecialchars(app_url('teacher/face_attendance.php')) ?>">Face Attendance</a>
                <a href="<?= htmlspecialchars(app_url('teacher/student_list.php')) ?>">Students</a>
            <?php elseif ($role === 'student'): ?>
                <a href="<?= htmlspecialchars(app_url('student/profile.php')) ?>">Profile</a>
                <a href="<?= htmlspecialchars(app_url('student/attendance_history.php')) ?>">History</a>
            <?php endif; ?>
            <?php if ($role): ?>
                <a href="<?= htmlspecialchars(app_url('logout.php')) ?>">Logout</a>
            <?php else: ?>
                <a href="<?= htmlspecialchars(app_url('login.php')) ?>">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

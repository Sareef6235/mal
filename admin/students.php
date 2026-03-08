<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('admin');

$students = $pdo->query('SELECT * FROM students ORDER BY id DESC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Students</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/header.php'; ?>
<div class="container">
    <h2>Students</h2>
    <p>
        <a href="<?= htmlspecialchars(app_url('admin/add_student.php')) ?>">Add Student</a> |
        <a href="<?= htmlspecialchars(app_url('qr/generate_qr.php')) ?>">Generate All QR Codes</a>
    </p>

    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Register</th>
            <th>Class</th>
            <th>QR</th>
        </tr>

        <?php foreach ($students as $s): ?>
            <tr>
                <td><?= (int) $s['id'] ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['register_no']) ?></td>
                <td><?= htmlspecialchars($s['class']) ?></td>
                <td>
                    <?php $qrPath = '../qrcodes/' . $s['register_no'] . '.png'; ?>
                    <?php if (is_file(__DIR__ . '/' . $qrPath)): ?>
                        <img src="<?= htmlspecialchars($qrPath) ?>" width="80" alt="QR">
                    <?php else: ?>
                        <small>Not generated</small>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>

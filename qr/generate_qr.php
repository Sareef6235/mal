<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/phpqrcode/qrlib.php';
require_auth('admin');

$students = $pdo->query('SELECT register_no FROM students')->fetchAll();
$generated = 0;
$errors = [];

foreach ($students as $s) {
    $file = __DIR__ . '/../qrcodes/' . $s['register_no'] . '.png';
    try {
        QRcode::png((string) $s['register_no'], $file);
        $generated++;
    } catch (Throwable $e) {
        $errors[] = 'Failed for ' . $s['register_no'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Generate QR</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/header.php'; ?>
<div class="container card">
    <h2>QR codes generated</h2>
    <p>Generated: <?= $generated ?></p>
    <?php if ($errors): ?>
        <div class="alert"><?= htmlspecialchars(implode(', ', $errors)) ?></div>
    <?php endif; ?>
    <p><a href="<?= htmlspecialchars(app_url('admin/students.php')) ?>">Back to Students</a></p>
</div>
</body>
</html>

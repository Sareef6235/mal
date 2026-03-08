<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid CSRF token';
    } else {
        $name = trim($_POST['name'] ?? '');
        $register = trim($_POST['register_no'] ?? '');
        $class = trim($_POST['class'] ?? '');

        if ($name === '' || $register === '' || $class === '') {
            $error = 'All fields are required';
        } else {
            $stmt = $pdo->prepare('INSERT INTO students(name,register_no,class) VALUES(?,?,?)');
            $stmt->execute([$name, $register, $class]);
            $message = 'Student Added Successfully';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add Student</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/header.php'; ?>
<div class="container card">
    <h2>Add Student</h2>

    <?php if ($message): ?><div class="success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        Name<br>
        <input type="text" name="name" required><br><br>

        Register No<br>
        <input type="text" name="register_no" required><br><br>

        Class<br>
        <input type="text" name="class" required><br><br>

        <button type="submit">Add Student</button>
    </form>
</div>
</body>
</html>

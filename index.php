<?php
require_once __DIR__ . '/config.php';

$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $class = trim($_POST['class'] ?? '');

    if ($name === '' || $class === '') {
        set_flash('error', 'Name and class are required.');
        redirect('/index.php');
    }

    $stmt = $pdo->prepare('INSERT INTO users (name, class) VALUES (?, ?)');
    $stmt->execute([$name, $class]);

    $_SESSION['user_id'] = (int)$pdo->lastInsertId();
    $_SESSION['user_name'] = $name;
    $_SESSION['user_class'] = $class;

    redirect('/quiz.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Quiz System</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Online Quiz / Exam System</h1>
            <nav class="menu">
                <a href="/index.php">Home</a>
                <a href="/admin/login.php">Admin Login</a>
            </nav>
        </div>

        <div class="glass-card">
            <?php if ($flash): ?>
                <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <h2>Start Your Quiz</h2>
            <p>Enter your details to begin. Questions are loaded dynamically from MySQL database.</p>

            <form method="POST" action="/index.php">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="class">Class</label>
                    <input type="text" id="class" name="class" placeholder="Enter your class" required>
                </div>

                <button type="submit" class="btn">Start Quiz</button>
            </form>
        </div>
    </div>
</body>
</html>

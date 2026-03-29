<?php
require_once __DIR__ . '/config.php';
require_quiz_user();

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT total_marks, scored_marks FROM results WHERE user_id = ?');
$stmt->execute([$userId]);
$result = $stmt->fetch();

if (!$result) {
    set_flash('error', 'Result not found. Please submit the quiz first.');
    redirect('/quiz.php');
}

$totalMarks = (int)$result['total_marks'];
$scoredMarks = (int)$result['scored_marks'];
$percentage = $totalMarks > 0 ? ($scoredMarks / $totalMarks) * 100 : 0;
$isPassed = $percentage >= 70;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Your Result</h1>
        <nav class="menu">
            <a href="/index.php">Home</a>
            <a href="/dashboard.php">Dashboard</a>
        </nav>
    </div>

    <div class="glass-card text-center">
        <h2><?= e((string)$_SESSION['user_name']) ?> (Class: <?= e((string)$_SESSION['user_class']) ?>)</h2>
        <p><strong>Score:</strong> <?= $scoredMarks ?> / <?= $totalMarks ?></p>
        <p><strong>Percentage:</strong> <?= number_format($percentage, 2) ?>%</p>

        <?php if ($isPassed): ?>
            <h3 class="typing-title" data-typing="Congratulations 🎉 You scored above 70%!" data-speed="45">Congratulations 🎉 You scored above 70%!</h3>
        <?php else: ?>
            <h3>Keep practicing! You can do better.</h3>
        <?php endif; ?>

        <a class="btn" href="/dashboard.php">View Dashboard</a>
    </div>
</div>
<script src="/assets/app.js"></script>
<script>
  if (window.quizApp) {
    window.quizApp.playTone(<?= $isPassed ? '900' : '350' ?>, 300);
  }
</script>
</body>
</html>

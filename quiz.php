<?php
require_once __DIR__ . '/config.php';
require_quiz_user();

$flash = get_flash();

$stmt = $pdo->query('SELECT id, question, option_a, option_b, option_c, option_d, marks FROM questions ORDER BY id ASC');
$questions = $stmt->fetchAll();
$totalQuestions = count($questions);

if ($totalQuestions === 0) {
    set_flash('error', 'No questions available. Please ask admin to add questions.');
    redirect('/index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Page</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Quiz Page</h1>
        <nav class="menu">
            <a href="/index.php">Home</a>
            <a href="/dashboard.php">Dashboard</a>
        </nav>
    </div>

    <div class="glass-card">
        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <h2>Welcome, <?= e((string)$_SESSION['user_name']) ?></h2>
        <p>Total Questions: <strong><?= $totalQuestions ?></strong></p>

        <form method="POST" action="/submit.php">
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card">
                    <p><strong>Question <?= $index + 1 ?>/<?= $totalQuestions ?>:</strong> <?= e($question['question']) ?></p>
                    <div class="options">
                        <?php foreach (['a', 'b', 'c', 'd'] as $option): ?>
                            <label class="option-item">
                                <input type="radio" name="answers[<?= (int)$question['id'] ?>]" value="<?= $option ?>" required>
                                <?= strtoupper($option) ?>. <?= e((string)$question['option_' . $option]) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn">Submit Quiz</button>
        </form>
    </div>
</div>
</body>
</html>

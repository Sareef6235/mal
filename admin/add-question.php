<?php
require_once __DIR__ . '/../config.php';
require_admin();

$flash = get_flash();
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$questionData = null;

if ($editId > 0) {
    $editStmt = $pdo->prepare('SELECT * FROM questions WHERE id = ?');
    $editStmt->execute([$editId]);
    $questionData = $editStmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $optionA = trim($_POST['option_a'] ?? '');
    $optionB = trim($_POST['option_b'] ?? '');
    $optionC = trim($_POST['option_c'] ?? '');
    $optionD = trim($_POST['option_d'] ?? '');
    $correct = strtolower(trim($_POST['correct_option'] ?? ''));
    $marks = (int)($_POST['marks'] ?? 1);
    $id = (int)($_POST['id'] ?? 0);

    if ($question === '' || $optionA === '' || $optionB === '' || $optionC === '' || $optionD === '' || !in_array($correct, ['a', 'b', 'c', 'd'], true)) {
        set_flash('error', 'Please fill all fields and choose valid correct option.');
        redirect('/admin/add-question.php' . ($id > 0 ? '?id=' . $id : ''));
    }

    if ($marks < 1) {
        $marks = 1;
    }

    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE questions
             SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, marks = ?
             WHERE id = ?'
        );
        $stmt->execute([$question, $optionA, $optionB, $optionC, $optionD, $correct, $marks, $id]);
        set_flash('success', 'Question updated successfully.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO questions (question, option_a, option_b, option_c, option_d, correct_option, marks)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$question, $optionA, $optionB, $optionC, $optionD, $correct, $marks]);
        set_flash('success', 'Question added successfully.');
    }

    redirect('/admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $questionData ? 'Edit' : 'Add' ?> Question</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1><?= $questionData ? 'Edit' : 'Add New' ?> Question</h1>
        <nav class="menu">
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/logout.php">Logout</a>
        </nav>
    </div>

    <div class="glass-card">
        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/add-question.php<?= $questionData ? '?id=' . (int)$questionData['id'] : '' ?>">
            <input type="hidden" name="id" value="<?= (int)($questionData['id'] ?? 0) ?>">
            <div class="form-group">
                <label>Question</label>
                <textarea name="question" rows="3" required><?= e((string)($questionData['question'] ?? '')) ?></textarea>
            </div>

            <div class="grid">
                <div class="form-group"><label>Option A</label><input name="option_a" required value="<?= e((string)($questionData['option_a'] ?? '')) ?>"></div>
                <div class="form-group"><label>Option B</label><input name="option_b" required value="<?= e((string)($questionData['option_b'] ?? '')) ?>"></div>
                <div class="form-group"><label>Option C</label><input name="option_c" required value="<?= e((string)($questionData['option_c'] ?? '')) ?>"></div>
                <div class="form-group"><label>Option D</label><input name="option_d" required value="<?= e((string)($questionData['option_d'] ?? '')) ?>"></div>
            </div>

            <div class="grid">
                <div class="form-group">
                    <label>Correct Option</label>
                    <select name="correct_option" required>
                        <?php $selected = strtolower((string)($questionData['correct_option'] ?? 'a')); ?>
                        <option value="a" <?= $selected === 'a' ? 'selected' : '' ?>>A</option>
                        <option value="b" <?= $selected === 'b' ? 'selected' : '' ?>>B</option>
                        <option value="c" <?= $selected === 'c' ? 'selected' : '' ?>>C</option>
                        <option value="d" <?= $selected === 'd' ? 'selected' : '' ?>>D</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Marks</label>
                    <input type="text" name="marks" value="<?= (int)($questionData['marks'] ?? 1) ?>" required>
                </div>
            </div>

            <button class="btn" type="submit"><?= $questionData ? 'Update' : 'Add' ?> Question</button>
        </form>
    </div>
</div>
</body>
</html>

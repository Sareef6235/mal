<?php
require_once __DIR__ . '/config.php';
require_quiz_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/quiz.php');
}

$userId = (int)$_SESSION['user_id'];
$userAnswers = $_POST['answers'] ?? [];

$stmt = $pdo->query('SELECT id, correct_option, marks FROM questions ORDER BY id ASC');
$questions = $stmt->fetchAll();

if (!$questions) {
    set_flash('error', 'No questions found.');
    redirect('/quiz.php');
}

$totalMarks = 0;
$scoredMarks = 0;

$pdo->beginTransaction();

try {
    $deleteAnswers = $pdo->prepare('DELETE FROM answers WHERE user_id = ?');
    $deleteAnswers->execute([$userId]);

    $deleteResult = $pdo->prepare('DELETE FROM results WHERE user_id = ?');
    $deleteResult->execute([$userId]);

    $insertAnswer = $pdo->prepare(
        'INSERT INTO answers (user_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)'
    );

    foreach ($questions as $question) {
        $questionId = (int)$question['id'];
        $marks = (int)$question['marks'];
        $totalMarks += $marks;

        $selected = strtolower((string)($userAnswers[$questionId] ?? ''));
        $selected = in_array($selected, ['a', 'b', 'c', 'd'], true) ? $selected : '';

        $correct = strtolower((string)$question['correct_option']);
        $isCorrect = ($selected !== '' && $selected === $correct) ? 1 : 0;

        if ($isCorrect === 1) {
            $scoredMarks += $marks;
        }

        $insertAnswer->execute([$userId, $questionId, $selected, $isCorrect]);
    }

    $insertResult = $pdo->prepare('INSERT INTO results (user_id, total_marks, scored_marks) VALUES (?, ?, ?)');
    $insertResult->execute([$userId, $totalMarks, $scoredMarks]);

    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    set_flash('error', 'Could not submit quiz. Please try again.');
    redirect('/quiz.php');
}

$_SESSION['total_marks'] = $totalMarks;
$_SESSION['scored_marks'] = $scoredMarks;

redirect('/result.php');

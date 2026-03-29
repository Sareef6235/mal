<?php
require_once __DIR__ . '/../config.php';
require_admin();

$flash = get_flash();

$statsStmt = $pdo->query('SELECT COUNT(*) AS total_users FROM users');
$totalUsers = (int)$statsStmt->fetch()['total_users'];

$qStmt = $pdo->query('SELECT COUNT(*) AS total_questions FROM questions');
$totalQuestions = (int)$qStmt->fetch()['total_questions'];

$topStmt = $pdo->query(
    'SELECT u.id, u.name, u.class, r.scored_marks, r.total_marks,
            CASE WHEN r.total_marks > 0 THEN (r.scored_marks / r.total_marks) * 100 ELSE 0 END AS percent
    FROM results r
    INNER JOIN users u ON u.id = r.user_id
    ORDER BY percent DESC, r.scored_marks DESC
    LIMIT 10'
);
$topScores = $topStmt->fetchAll();

$usersStmt = $pdo->query(
    'SELECT u.id, u.name, u.class, COALESCE(r.scored_marks, 0) AS scored_marks, COALESCE(r.total_marks, 0) AS total_marks
     FROM users u
     LEFT JOIN results r ON r.user_id = u.id
     ORDER BY u.id DESC'
);
$users = $usersStmt->fetchAll();

$questionsStmt = $pdo->query('SELECT * FROM questions ORDER BY id DESC');
$questions = $questionsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Admin Dashboard</h1>
        <nav class="menu">
            <a href="/admin/dashboard.php">Dashboard</a>
            <a href="/admin/add-question.php">Add Question</a>
            <a href="/index.php">User Home</a>
            <a href="/admin/logout.php">Logout</a>
        </nav>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="stat-card"><h3>Total Users</h3><p><?= $totalUsers ?></p></div>
        <div class="stat-card"><h3>Total Questions</h3><p><?= $totalQuestions ?></p></div>
        <div class="stat-card"><h3>Top Score</h3><p><?= $topScores ? (int)$topScores[0]['scored_marks'] : 0 ?></p></div>
    </div>

    <div class="glass-card" style="margin-top:1rem;">
        <h2>Top 10 Leaderboard</h2>
        <div class="table-wrap">
            <table>
                <tr><th>#</th><th>Name</th><th>Class</th><th>Score</th><th>Percent</th><th>Badge</th></tr>
                <?php if (!$topScores): ?>
                    <tr><td colspan="6">No results available.</td></tr>
                <?php else: foreach ($topScores as $i => $row): ?>
                    <tr class="<?= $i === 0 ? 'top-row' : '' ?>">
                        <td><?= $i + 1 ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['class']) ?></td>
                        <td><?= (int)$row['scored_marks'] ?> / <?= (int)$row['total_marks'] ?></td>
                        <td><?= number_format((float)$row['percent'], 2) ?>%</td>
                        <td><?= $i === 0 ? '<span class="badge">Top scorer 🔥</span>' : '-' ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </table>
        </div>
    </div>

    <div class="glass-card" style="margin-top:1rem;">
        <h2>Manage Questions (Edit / Delete)</h2>
        <div class="table-wrap">
            <table>
                <tr><th>ID</th><th>Question</th><th>Correct</th><th>Marks</th><th>Actions</th></tr>
                <?php if (!$questions): ?>
                    <tr><td colspan="5">No questions found.</td></tr>
                <?php else: foreach ($questions as $q): ?>
                    <tr>
                        <td><?= (int)$q['id'] ?></td>
                        <td><?= e($q['question']) ?></td>
                        <td><?= strtoupper(e($q['correct_option'])) ?></td>
                        <td><?= (int)$q['marks'] ?></td>
                        <td>
                            <a href="/admin/add-question.php?id=<?= (int)$q['id'] ?>">Edit</a> |
                            <a href="/admin/delete-question.php?id=<?= (int)$q['id'] ?>" onclick="return confirm('Delete this question?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </table>
        </div>
    </div>

    <div class="glass-card" style="margin-top:1rem;">
        <h2>All Users and Scores</h2>
        <div class="table-wrap">
            <table>
                <tr><th>ID</th><th>Name</th><th>Class</th><th>Score</th></tr>
                <?php if (!$users): ?>
                    <tr><td colspan="4">No users found.</td></tr>
                <?php else: foreach ($users as $user): ?>
                    <tr>
                        <td><?= (int)$user['id'] ?></td>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['class']) ?></td>
                        <td><?= (int)$user['scored_marks'] ?> / <?= (int)$user['total_marks'] ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>

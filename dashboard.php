<?php
require_once __DIR__ . '/config.php';
require_quiz_user();

$userId = (int)$_SESSION['user_id'];

$myStmt = $pdo->prepare(
    'SELECT u.name, u.class, r.scored_marks, r.total_marks
     FROM users u
     LEFT JOIN results r ON r.user_id = u.id
     WHERE u.id = ?'
);
$myStmt->execute([$userId]);
$myData = $myStmt->fetch();

$topStmt = $pdo->query(
    'SELECT u.name, u.class, r.scored_marks, r.total_marks,
            CASE WHEN r.total_marks > 0 THEN (r.scored_marks / r.total_marks) * 100 ELSE 0 END AS percent
     FROM results r
     INNER JOIN users u ON u.id = r.user_id
     ORDER BY percent DESC, r.scored_marks DESC
     LIMIT 10'
);
$topScores = $topStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>User Dashboard</h1>
        <nav class="menu">
            <a href="/index.php">Home</a>
            <a href="/quiz.php">Quiz</a>
            <a href="/result.php">Result</a>
        </nav>
    </div>

    <div class="grid">
        <div class="stat-card">
            <h3>Your Name</h3>
            <p><?= e((string)($myData['name'] ?? $_SESSION['user_name'])) ?></p>
        </div>
        <div class="stat-card">
            <h3>Your Class</h3>
            <p><?= e((string)($myData['class'] ?? $_SESSION['user_class'])) ?></p>
        </div>
        <div class="stat-card">
            <h3>Your Score</h3>
            <p><?= (int)($myData['scored_marks'] ?? 0) ?> / <?= (int)($myData['total_marks'] ?? 0) ?></p>
        </div>
    </div>

    <div class="glass-card" style="margin-top:1rem;">
        <h2>Top 10 Highest Scores</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Score</th>
                        <th>Percent</th>
                        <th>Badge</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$topScores): ?>
                    <tr><td colspan="6">No results yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($topScores as $idx => $row): ?>
                        <tr class="<?= $idx === 0 ? 'top-row' : '' ?>">
                            <td><?= $idx + 1 ?></td>
                            <td><?= e($row['name']) ?></td>
                            <td><?= e($row['class']) ?></td>
                            <td><?= (int)$row['scored_marks'] ?> / <?= (int)$row['total_marks'] ?></td>
                            <td><?= number_format((float)$row['percent'], 2) ?>%</td>
                            <td>
                                <?php if ($idx === 0): ?>
                                    <span class="badge">Top scorer 🔥</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>

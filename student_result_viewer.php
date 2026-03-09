<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$db = db_connect($config['db']);
if (!$db) {
    die('DB connection failed.');
}

$students = $db->query('SELECT id, COALESCE(register_no, student_uid) AS register_no, full_name AS name FROM students ORDER BY full_name')->fetchAll() ?: [];

$studentId = null;
$result = null;
if (isset($_POST['student_id']) && (int)$_POST['student_id'] > 0) {
    $studentId = (int)$_POST['student_id'];
    $result = fetch_student_result($db, $config['app'], $studentId);
}

render_header('Student Result Viewer');
?>

<div class="card">
    <h2>Select Student to View Result</h2>
    <form method="post">
        <select name="student_id" required>
            <option value="">-- Select Student --</option>
            <?php foreach ($students as $stu): ?>
                <option value="<?= (int)$stu['id'] ?>" <?= ((int)$stu['id'] === $studentId) ? 'selected' : '' ?>>
                    <?= e((string)$stu['name'] . ' (' . (string)$stu['register_no'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">View Result</button>
    </form>
</div>

<?php if ($studentId !== null): ?>
    <div class="card">
        <?php if ($result): ?>
            <h3>Result for <?= e((string)$result['student']['name']) ?> (<?= e((string)$result['student']['register_no']) ?>)</h3>
            <table>
                <tr><th>Subject</th><th>Mark</th><th>Max Mark</th><th>Status</th></tr>
                <?php foreach ($result['subjects'] as $sub): ?>
                    <tr>
                        <td><?= e((string)$sub['name']) ?></td>
                        <td><?= e((string)$sub['mark']) ?></td>
                        <td><?= e((string)$sub['max_mark']) ?></td>
                        <td><?= e((string)$sub['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p><strong>Total:</strong> <?= e((string)$result['obtained_total']) ?> / <?= e((string)$result['possible_total']) ?></p>
            <p><strong>Percentage:</strong> <?= e((string)$result['percentage']) ?>%</p>
            <p><strong>Grade:</strong> <?= e((string)$result['grade']) ?></p>
            <p><strong>Status:</strong> <?= e((string)$result['status']) ?></p>
            <p><strong>Class Rank:</strong> <?= e((string)$result['class_rank']) ?></p>
            <p><strong>Overall Rank:</strong> <?= e((string)$result['overall_rank']) ?></p>
            <p><?= e((string)$result['promotion_message']) ?></p>
        <?php else: ?>
            <p class="msg-bad">No result found for this student.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php render_footer();

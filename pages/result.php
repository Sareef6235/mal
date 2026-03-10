<?php
require_once __DIR__ . '/../config.php';

$examId = 1;
$nameExpr = student_name_sql();
$stmt = $db->query('SELECT ' . $nameExpr . ' AS name, COALESCE(students.register_no, students.register_number) AS register_number, SUM(marks.mark) AS total FROM marks JOIN students ON students.id = marks.student_id WHERE marks.exam_id = ' . (int)$examId . ' GROUP BY students.id, COALESCE(students.register_no, students.register_number), ' . $nameExpr . ' ORDER BY total DESC');
$results = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
$rank = 1;
?>
<h2>Exam Results</h2>
<table border="1" cellpadding="10">
<tr><th>Rank</th><th>Register No</th><th>Name</th><th>Total</th></tr>
<?php foreach ($results as $row): ?>
<tr>
<td><?= $rank++ ?></td>
<td><?= e((string)$row['register_number']) ?></td>
<td><?= e((string)$row['name']) ?></td>
<td><?= e((string)$row['total']) ?></td>
</tr>
<?php endforeach; ?>
</table>

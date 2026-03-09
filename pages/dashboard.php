<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
$db = connect_db($config['db']);
ensure_core_tables($db);
$stats = [
    'students' => (int)$db->query('SELECT COUNT(*) FROM students')->fetchColumn(),
    'exams' => (int)$db->query('SELECT COUNT(*) FROM exams')->fetchColumn(),
    'subjects' => (int)$db->query('SELECT COUNT(*) FROM subjects')->fetchColumn(),
];
$latest = $db->query('SELECT exam_id, student_id, subject_id, mark FROM marks ORDER BY exam_id DESC LIMIT 10')->fetchAll() ?: [];
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<h3>Dashboard</h3>
<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card card-body">Total Students: <strong><?= $stats['students'] ?></strong></div></div>
  <div class="col-md-4"><div class="card card-body">Total Exams: <strong><?= $stats['exams'] ?></strong></div></div>
  <div class="col-md-4"><div class="card card-body">Total Subjects: <strong><?= $stats['subjects'] ?></strong></div></div>
</div>
<table class="table table-bordered"><tr><th>Exam</th><th>Student</th><th>Subject</th><th>Mark</th></tr>
<?php foreach ($latest as $r): ?><tr><td><?= (int)$r['exam_id'] ?></td><td><?= (int)$r['student_id'] ?></td><td><?= (int)$r['subject_id'] ?></td><td><?= h((string)$r['mark']) ?></td></tr><?php endforeach; ?>
</table>
<?php include __DIR__ . '/../layout/footer.php';

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
$latest = $db->query('SELECT exam_id, student_id, subject_id, mark FROM marks ORDER BY id DESC LIMIT 10')->fetchAll() ?: [];
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<h3 class="mb-3">Dashboard</h3>
<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card card-soft stat-card p-3"><div class="d-flex justify-content-between"><div><small class="text-muted">Total Students</small><h4><?= $stats['students'] ?></h4></div><i class="bi bi-people fs-2 text-primary"></i></div></div></div>
  <div class="col-md-4"><div class="card card-soft stat-card p-3"><div class="d-flex justify-content-between"><div><small class="text-muted">Total Exams</small><h4><?= $stats['exams'] ?></h4></div><i class="bi bi-journal-check fs-2 text-success"></i></div></div></div>
  <div class="col-md-4"><div class="card card-soft stat-card p-3"><div class="d-flex justify-content-between"><div><small class="text-muted">Total Subjects</small><h4><?= $stats['subjects'] ?></h4></div><i class="bi bi-journal-bookmark fs-2 text-primary"></i></div></div></div>
</div>
<div class="card card-soft p-3">
  <h5>Latest Results</h5>
  <div class="table-responsive">
    <table class="table table-striped table-hover table-modern">
      <thead><tr><th>Exam</th><th>Student</th><th>Subject</th><th>Mark</th></tr></thead>
      <tbody><?php foreach ($latest as $r): ?><tr><td><?= (int)$r['exam_id'] ?></td><td><?= (int)$r['student_id'] ?></td><td><?= (int)$r['subject_id'] ?></td><td><span class="badge text-bg-primary"><?= h((string)$r['mark']) ?></span></td></tr><?php endforeach; ?></tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../layout/footer.php';

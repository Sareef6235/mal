<?php
require __DIR__ . '/../db.php';
$db = connect_db($config['db']);
ensure_core_tables($db);
$studentId = (int)($_GET['student_id'] ?? 0);
$examId = (int)($_GET['exam_id'] ?? 0);
$details = [];
$student = null;
if ($studentId > 0 && $examId > 0) {
    $st = $db->prepare('SELECT full_name, COALESCE(register_no, student_uid) AS register_no, class_name FROM students WHERE id=:id');
    $st->execute(['id' => $studentId]);
    $student = $st->fetch();
    $st2 = $db->prepare('SELECT sub.subject_name,m.mark FROM marks m INNER JOIN subjects sub ON sub.id=m.subject_id WHERE m.student_id=:sid AND m.exam_id=:eid ORDER BY sub.display_order');
    $st2->execute(['sid' => $studentId, 'eid' => $examId]);
    $details = $st2->fetchAll() ?: [];
}
$total = array_reduce($details, fn($c,$d)=>$c+(float)$d['mark'], 0.0);
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<h3 class="mb-3">Marksheet (Printable)</h3>
<div class="card card-soft p-3 mb-3"><form class="row g-2"><div class="col-md-3"><input class="form-control" name="exam_id" value="<?= $examId?:'' ?>" placeholder="Exam ID"></div><div class="col-md-3"><input class="form-control" name="student_id" value="<?= $studentId?:'' ?>" placeholder="Student ID"></div><div class="col-md-2"><button class="btn btn-primary w-100">Load</button></div></form></div>
<div class="card card-soft p-3" id="marksheetPrintArea">
  <h4 class="text-center mb-3">Madrasa Result Marksheet</h4>
  <?php if($student): ?><p><strong>Name:</strong> <?= h((string)$student['full_name']) ?> | <strong>Register:</strong> <?= h((string)$student['register_no']) ?> | <strong>Class:</strong> <?= h((string)$student['class_name']) ?></p><?php endif; ?>
  <table class="table table-bordered"><thead><tr><th>Subject</th><th>Mark</th></tr></thead><tbody><?php foreach($details as $d): ?><tr><td><?= h((string)$d['subject_name']) ?></td><td><?= h((string)$d['mark']) ?></td></tr><?php endforeach; ?></tbody></table>
  <p><strong>Total:</strong> <?= h((string)$total) ?></p>
  <div class="d-flex justify-content-between"><span>Signature: __________________</span><button class="btn btn-secondary" onclick="window.print()">Print / PDF</button></div>
</div>
<?php include __DIR__ . '/../layout/footer.php';

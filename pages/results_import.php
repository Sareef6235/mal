<?php
require __DIR__ . '/../db.php';
$db = connect_db($config['db']);
ensure_core_tables($db);
$report = ['imported' => 0, 'skipped' => 0, 'errors' => []];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['csv_file']['tmp_name'])) {
    $report = import_exam_marks_from_csv($db, $_FILES['csv_file']['tmp_name']);
}
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<h3>Results Import</h3>
<p>CSV format: <code>exam_id,register_number,subject_id,mark</code></p>
<form method="post" enctype="multipart/form-data" class="mb-3">
  <input type="file" class="form-control" name="csv_file" accept=".csv" required>
  <button class="btn btn-primary mt-2">Import CSV</button>
</form>
<div class="alert alert-info">Imported: <?= (int)$report['imported'] ?> | Skipped: <?= (int)$report['skipped'] ?></div>
<?php if ($report['errors']): ?><div class="alert alert-warning"><ul><?php foreach($report['errors'] as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php include __DIR__ . '/../layout/footer.php';

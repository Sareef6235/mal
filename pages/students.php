<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
$db = connect_db($config['db']);
ensure_core_tables($db);
$rows = $db->query('SELECT id, register_no, full_name, class_name, phone, photo_path FROM students ORDER BY id DESC')->fetchAll() ?: [];
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Students</h3>
  <a class="btn btn-primary" href="student_add.php"><i class="bi bi-plus-circle me-1"></i>Add Student</a>
</div>
<div class="card card-soft p-3">
  <div class="mb-3"><input class="form-control" placeholder="Search students..." data-table-search="#studentsTable"></div>
  <div class="table-responsive">
    <table class="table table-striped table-hover table-modern" id="studentsTable">
      <thead><tr><th>ID</th><th>Photo</th><th>Register</th><th>Name</th><th>Class</th><th>Phone</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td>
            <?php if (!empty($r['photo_path'])): ?>
              <img class="student-photo" src="<?= e('/uploads/' . (string)$r['photo_path']) ?>" alt="photo">
            <?php else: ?>
              <span class="badge text-bg-secondary">No Photo</span>
            <?php endif; ?>
          </td>
          <td><?= h((string)$r['register_no']) ?></td>
          <td><?= h((string)$r['full_name']) ?></td>
          <td><?= h((string)$r['class_name']) ?></td>
          <td><?= h((string)$r['phone']) ?></td>
          <td class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-primary" href="student_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
            <?php if (!empty($r['photo_path'])): ?><a class="btn btn-sm btn-outline-success" href="/download.php?file=<?= urlencode((string)$r['photo_path']) ?>">Download Photo</a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../layout/footer.php';

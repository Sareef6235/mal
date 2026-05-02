<?php require_once 'config.php'; requireLogin();
$totalStudents = $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalCollected = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM fees WHERE status='Verified'")->fetchColumn();
$pendingFees = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM fees WHERE status='Pending'")->fetchColumn();
$classTotals = $pdo->query("SELECT class, COALESCE(SUM(amount),0) total FROM fees GROUP BY class ORDER BY class")->fetchAll();
include 'header.php'; ?>
<div class="row g-3">
  <div class="col-md-4"><div class="card glass card-stat p-3"><h6>Total Students</h6><h2><?= (int)$totalStudents ?></h2></div></div>
  <div class="col-md-4"><div class="card glass card-stat p-3"><h6>Total Fees Collected</h6><h2>₹<?= number_format((float)$totalCollected,2) ?></h2></div></div>
  <div class="col-md-4"><div class="card glass card-stat p-3"><h6>Pending Fees</h6><h2>₹<?= number_format((float)$pendingFees,2) ?></h2></div></div>
</div>
<div class="card glass mt-4 p-3">
<h5>Class-wise Totals</h5>
<table class="table table-dark table-striped"><tr><th>Class</th><th>Total</th></tr>
<?php foreach($classTotals as $row): ?><tr><td><?= h($row['class']) ?></td><td>₹<?= number_format((float)$row['total'],2) ?></td></tr><?php endforeach; ?>
</table></div>
<?php include 'footer.php'; ?>

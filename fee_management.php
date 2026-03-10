<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/bootstrap.php';
$db = db_connect($config['db']);
if (!$db instanceof PDO) die('Database connection failed');

$message=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $studentName = trim((string)($_POST['student_name'] ?? ''));
  $madrasa = trim((string)($_POST['madrasa_name'] ?? ''));
  $month = trim((string)($_POST['month_key'] ?? ''));
  $amount = (float)($_POST['amount'] ?? 0);
  $paid = (float)($_POST['paid_amount'] ?? 0);
  $due = max(0, $amount - $paid);
  $status = $due <= 0 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Pending');
  if ($studentName==='' || $madrasa==='' || $month==='' || $amount<=0) $error='Fill required fee fields.';
  else {
    $st=$db->prepare('INSERT INTO fees(student_name,madrasa_name,month_key,amount,paid_amount,due_amount,status,paid_at) VALUES(?,?,?,?,?,?,?,?)');
    $st->execute([$studentName,$madrasa,$month,$amount,$paid,$due,$status,$paid>0?date('Y-m-d H:i:s'):null]);
    $message='Fee entry saved.';
  }
}

if (isset($_GET['export']) && $_GET['export']==='csv') {
  $rows = $db->query('SELECT * FROM fees ORDER BY id DESC')->fetchAll();
  header('Content-Type:text/csv');
  header('Content-Disposition: attachment; filename="fees-export.csv"');
  $out=fopen('php://output','w');
  fputcsv($out,['id','student_name','madrasa_name','month_key','amount','paid_amount','due_amount','status','created_at']);
  foreach($rows as $r) fputcsv($out,[$r['id'],$r['student_name'],$r['madrasa_name'],$r['month_key'],$r['amount'],$r['paid_amount'],$r['due_amount'],$r['status'],$r['created_at']]);
  fclose($out); exit;
}

$rows = $db->query('SELECT * FROM fees ORDER BY id DESC LIMIT 300')->fetchAll();
$total = array_sum(array_map(fn($r)=>(float)$r['amount'],$rows));
$paidTotal = array_sum(array_map(fn($r)=>(float)$r['paid_amount'],$rows));
$dueTotal = array_sum(array_map(fn($r)=>(float)$r['due_amount'],$rows));

render_header('Fee Management');
?>
<div class="card">
<?php if($message): ?><p class="msg-ok"><?= e($message) ?></p><?php endif; ?>
<?php if($error): ?><p class="msg-bad"><?= e($error) ?></p><?php endif; ?>
<form method="post" style="display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
<input name="student_name" placeholder="Student Name" required>
<input name="madrasa_name" placeholder="Madrasa Name" required>
<input type="month" name="month_key" required>
<input type="number" step="0.01" name="amount" placeholder="Fee Amount" required>
<input type="number" step="0.01" name="paid_amount" placeholder="Paid Amount" value="0">
<button type="submit">Save Fee</button>
</form>
<p class="small">Total: <?= number_format($total,2) ?> | Paid: <?= number_format($paidTotal,2) ?> | Due: <?= number_format($dueTotal,2) ?> | <a href="?export=csv">Export CSV</a></p>
</div>
<div class="card" style="overflow:auto"><table><thead><tr><th>ID</th><th>Student</th><th>Madrasa</th><th>Month</th><th>Amount</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= e((string)$r['student_name']) ?></td><td><?= e((string)$r['madrasa_name']) ?></td><td><?= e((string)$r['month_key']) ?></td><td><?= e((string)$r['amount']) ?></td><td><?= e((string)$r['paid_amount']) ?></td><td><?= e((string)$r['due_amount']) ?></td><td><?= e((string)$r['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php render_footer();

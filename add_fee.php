<?php require_once 'config.php'; requireLogin();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $studentName = trim($_POST['student_name']);
  $class = trim($_POST['class']);
  $amount = (float)$_POST['amount'];
  $date = $_POST['date'];
  $method = $_POST['payment_method'];

  $stmt = $pdo->prepare('SELECT id FROM students WHERE name=? AND class=? LIMIT 1');
  $stmt->execute([$studentName,$class]);
  $studentId = $stmt->fetchColumn();
  if(!$studentId){
    $ins = $pdo->prepare('INSERT INTO students(name,class) VALUES(?,?)');
    $ins->execute([$studentName,$class]);
    $studentId = $pdo->lastInsertId();
  }
  $insFee = $pdo->prepare('INSERT INTO fees(student_id,class,amount,payment_method,date,status) VALUES(?,?,?,?,?,?)');
  $insFee->execute([$studentId,$class,$amount,$method,$date,'Pending']);
  $msg='Fee entry added successfully.';
}
include 'header.php'; ?>
<div class="card glass p-4">
<h4>Add Fee Entry</h4>
<?php if($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<form method="post" onsubmit="return validateFee()">
<div class="row g-2">
<div class="col-md-6"><input class="form-control" name="student_name" id="student_name" placeholder="Student Name" required></div>
<div class="col-md-6"><input class="form-control" name="class" id="class" placeholder="Class" required></div>
<div class="col-md-4"><input class="form-control" type="number" step="0.01" min="1" name="amount" id="amount" placeholder="Amount" required></div>
<div class="col-md-4"><input class="form-control" type="date" name="date" required value="<?= date('Y-m-d') ?>"></div>
<div class="col-md-4"><select class="form-select" name="payment_method" required><option>Cash</option><option>UPI</option><option>GPay</option><option>PhonePe</option></select></div>
</div><button class="btn btn-primary mt-3">Save</button>
</form></div>
<script>
function validateFee(){const amount=parseFloat(document.getElementById('amount').value||0);if(amount<=0){alert('Amount must be greater than 0');return false;}return true;}
</script>
<?php include 'footer.php'; ?>

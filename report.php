<?php require_once 'config.php'; requireLogin();
$class = $_GET['class'] ?? '';
$params=[]; $where='';
if($class!==''){ $where=' WHERE f.class=?'; $params[]=$class; }
$stmt=$pdo->prepare("SELECT f.class,s.name student_name,f.amount,f.payment_method,f.date,f.status FROM fees f JOIN students s ON s.id=f.student_id $where ORDER BY f.class,f.date");
$stmt->execute($params);$rows=$stmt->fetchAll();
include 'header.php'; ?>
<div class="card glass p-3">
<h4>PDF Reports</h4>
<form class="row g-2 mb-3"><div class="col-md-4"><input class="form-control" name="class" placeholder="Class name for report" value="<?= h($class) ?>"></div><div class="col-md-2"><button class="btn btn-outline-light">Load</button></div></form>
<div id="reportArea" class="bg-white text-dark p-3 rounded">
<h5>Madras Fee Report <?= $class?'- '.h($class):'' ?></h5>
<table class="table table-bordered"><tr><th>Class</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr>
<?php $total=0; foreach($rows as $r): $total += (float)$r['amount']; ?><tr><td><?= h($r['class']) ?></td><td><?= h($r['student_name']) ?></td><td><?= number_format((float)$r['amount'],2) ?></td><td><?= h($r['payment_method']) ?></td><td><?= h($r['date']) ?></td><td><?= h($r['status']) ?></td></tr><?php endforeach; ?>
<tr><th colspan="2">Total</th><th colspan="4">₹<?= number_format($total,2) ?></th></tr></table>
</div>
<button class="btn btn-danger" onclick="window.print()">Print</button>
<button class="btn btn-primary" onclick="downloadPDF()">Download PDF</button>
<a class="btn btn-success" id="waBtn" target="_blank">Share WhatsApp</a>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
const waText = encodeURIComponent('Madras Fee Report Total: ₹<?= number_format($total,2) ?>');
document.getElementById('waBtn').href = `https://wa.me/?text=${waText}`;
async function downloadPDF(){
 const { jsPDF } = window.jspdf;
 const area = document.getElementById('reportArea');
 const canvas = await html2canvas(area, {scale: 2});
 const img = canvas.toDataURL('image/png');
 const pdf = new jsPDF('p','mm','a4');
 const w = 190, h = canvas.height * w / canvas.width;
 pdf.addImage(img,'PNG',10,10,w,h);
 pdf.save('madras-fee-report.pdf');
}
</script>
<?php include 'footer.php'; ?>

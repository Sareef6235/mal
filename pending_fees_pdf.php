<?php
require_once 'config.php';
requireLogin();

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function pendingFeeFilters(): array { return ['student'=>trim($_GET['student']??''),'class'=>trim($_GET['class']??''),'month'=>trim($_GET['month']??''),'payment_method'=>trim($_GET['payment_method']??''),'date_from'=>trim($_GET['date_from']??''),'date_to'=>trim($_GET['date_to']??'')]; }
function buildPendingFeesQuery(array $filters): array {
    $sql = "SELECT f.id, f.student_id, f.class, f.amount, f.payment_method, f.date, f.month, f.status, f.months_count, f.verified_months, u.name AS student_name, u.class AS student_class FROM fees f JOIN users u ON f.student_id = u.id WHERE u.role='student' AND LOWER(COALESCE(f.status,'')) <> 'verified'";
    $params = [];
    if ($filters['student'] !== '') { $sql .= " AND u.name LIKE ?"; $params[] = '%'.$filters['student'].'%'; }
    if ($filters['class'] !== '') { $sql .= " AND u.class = ?"; $params[] = $filters['class']; }
    if ($filters['month'] !== '') { $sql .= " AND DATE_FORMAT(f.month,'%Y-%m') = ?"; $params[] = $filters['month']; }
    if ($filters['payment_method'] !== '') { $sql .= " AND f.payment_method = ?"; $params[] = $filters['payment_method']; }
    if ($filters['date_from'] !== '') { $sql .= " AND DATE(f.date) >= ?"; $params[] = $filters['date_from']; }
    if ($filters['date_to'] !== '') { $sql .= " AND DATE(f.date) <= ?"; $params[] = $filters['date_to']; }
    $sql .= " ORDER BY f.date DESC, f.id DESC";
    return [$sql, $params];
}
function feeMonths($month, int $monthsCount): string { $start = $month ? date('Y-m', strtotime($month)) : date('Y-m'); $dt = new DateTime($start.'-01'); $out=[]; for($i=0;$i<max(1,$monthsCount);$i++){ $out[]=date('M Y', strtotime($dt->format('Y-m').'-01')); $dt->modify('+1 month'); } return implode(', ', $out); }
$filters = pendingFeeFilters();
[$sql, $params] = buildPendingFeesQuery($filters);
$stmt = $pdo->prepare($sql); $stmt->execute($params); $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalStudents = count(array_unique(array_column($fees, 'student_id'))); $totalAmount = 0; foreach ($fees as $f) { $totalAmount += (float)$f['amount'] * (int)($f['months_count'] ?: 1); }
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html><html><head><meta charset="utf-8"><title>Pending Fees PDF</title><style>@page{size:A4 landscape;margin:14mm}body{font-family:Arial,sans-serif;color:#111827}.header{border-bottom:3px solid #2563eb;padding-bottom:12px;margin-bottom:14px}.header h1{margin:0;color:#2563eb}.meta{display:flex;justify-content:space-between;font-size:12px}.summary{display:flex;gap:10px;margin:12px 0}.card{border:1px solid #dbeafe;border-radius:10px;padding:10px;background:#eff6ff;flex:1}.filters{font-size:12px;margin:10px 0;padding:8px;background:#f8fafc;border-radius:8px}table{width:100%;border-collapse:collapse;font-size:11px}th{background:#111827;color:white}th,td{border:1px solid #d1d5db;padding:7px;text-align:left}tr:nth-child(even){background:#f9fafb}.footer{position:fixed;bottom:4mm;left:14mm;right:14mm;text-align:center;font-size:10px;color:#6b7280}.print{margin:10px 0}@media print{.print{display:none}}</style></head><body>
<button class="print" onclick="window.print()">Print / Save PDF</button>
<div class="header"><h1>School Name - Pending Fee Report</h1><div class="meta"><span>Generated: <?= h(date('d M Y h:i A')) ?></span><span>Landscape A4</span></div></div>
<div class="summary"><div class="card"><strong>Total Students</strong><br><?= (int)$totalStudents ?></div><div class="card"><strong>Total Entries</strong><br><?= count($fees) ?></div><div class="card"><strong>Total Amount</strong><br>₹<?= number_format($totalAmount,2) ?></div></div>
<div class="filters"><strong>Filters Used:</strong> Student: <?= h($filters['student'] ?: 'All') ?> | Class: <?= h($filters['class'] ?: 'All') ?> | Month: <?= h($filters['month'] ?: 'All') ?> | Method: <?= h($filters['payment_method'] ?: 'All') ?> | Date: <?= h(($filters['date_from'] ?: 'Any').' to '.($filters['date_to'] ?: 'Any')) ?></div>
<table><thead><tr><th>No</th><th>Student</th><th>Class</th><th>Fee</th><th>Count</th><th>Total</th><th>Month(s)</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody><?php $no=1; foreach($fees as $f): $count=(int)($f['months_count']?:1); $total=(float)$f['amount']*$count; ?><tr><td><?= $no++ ?></td><td><?= h($f['student_name']) ?></td><td><?= h($f['student_class'] ?: $f['class']) ?></td><td>₹<?= number_format((float)$f['amount'],2) ?></td><td><?= $count ?></td><td>₹<?= number_format($total,2) ?></td><td><?= h(feeMonths($f['month'],$count)) ?></td><td><?= h($f['payment_method']) ?></td><td><?= h($f['date']) ?></td><td><?= h($f['status']) ?></td></tr><?php endforeach; ?></tbody></table>
<div class="footer">Pending Fee Export • Page <span class="pageNumber"></span></div><script>window.onload=()=>setTimeout(()=>window.print(),300)</script></body></html>

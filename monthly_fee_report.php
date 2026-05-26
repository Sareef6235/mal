<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';
requireLogin();

$userId    = $_SESSION['user_id'] ?? 0;
$userRole  = $_SESSION['role'] ?? 'student';
$userClass = $_SESSION['class'] ?? '';
$classes   = $_SESSION['classes'] ?? [];

if (empty($classes) && !empty($userClass)) $classes = [$userClass];
if (empty($classes)) $classes = ['___NOCLASS___'];
$placeholders = implode(',', array_fill(0, count($classes), '?'));

$selectedMonth = $_GET['month'] ?? date('Y-m');
$monthlyFee = 100;
$selectedClass = $_GET['class'] ?? 'ALL';

$classStmt = $pdo->query("SELECT DISTINCT class FROM users WHERE role='student' AND class IS NOT NULL AND class != '' ORDER BY class ASC");
$allClasses = $classStmt->fetchAll(PDO::FETCH_COLUMN);

$classFilterSql = '';
$classFilterParams = [];
if ($selectedClass !== 'ALL') {
    $classFilterSql = " AND u.class = ? ";
    $classFilterParams[] = $selectedClass;
}

if ($userRole === 'admin') {
    $sql = "SELECT u.id,u.name,u.class,COALESCE(SUM(f.amount),0) as paid_amount
            FROM users u
            LEFT JOIN fees f ON f.student_id = u.id AND DATE_FORMAT(f.created_at,'%Y-%m') = ? AND f.status='Verified'
            WHERE u.role='student' $classFilterSql GROUP BY u.id ORDER BY u.class ASC, u.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$selectedMonth], $classFilterParams));
} elseif ($userRole === 'ustad') {
    $sql = "SELECT u.id,u.name,u.class,COALESCE(SUM(f.amount),0) as paid_amount
            FROM users u
            LEFT JOIN fees f ON f.student_id = u.id AND DATE_FORMAT(f.created_at,'%Y-%m') = ? AND f.status='Verified'
            WHERE u.role='student' AND u.class IN ($placeholders) $classFilterSql GROUP BY u.id ORDER BY u.class ASC, u.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$selectedMonth], $classes, $classFilterParams));
} else {
    $sql = "SELECT u.id,u.name,u.class,COALESCE(SUM(f.amount),0) as paid_amount
            FROM users u
            LEFT JOIN fees f ON f.student_id = u.id AND DATE_FORMAT(f.created_at,'%Y-%m') = ? AND f.status='Verified'
            WHERE u.role='student' AND u.class = ? GROUP BY u.id ORDER BY u.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selectedMonth, $userClass]);
}

$allMonthlyStudents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$allowedLimits = [10,20,30,40,50,60,70,80,90,100,120,140,160];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if (!in_array($limit, $allowedLimits, true)) $limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalRows = count($allMonthlyStudents);
$totalPages = max(1, (int)ceil($totalRows / $limit));
$offset = ($page - 1) * $limit;
$monthlyStudents = array_slice($allMonthlyStudents, $offset, $limit);

$paidStudents = 0; $pendingStudents = 0; $totalMonthlyCollection = 0;
foreach ($allMonthlyStudents as $s) {
    $paidAmount = (float)$s['paid_amount'];
    $totalMonthlyCollection += $paidAmount;
    if ($paidAmount >= $monthlyFee) $paidStudents++; else $pendingStudents++;
}
$remainingAmount = max(0, ($totalRows * $monthlyFee) - $totalMonthlyCollection);
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Monthly Fee Dashboard</title>
<link rel="manifest" href="manifest.json"><meta name="theme-color" content="#0a1020">
<link rel="icon" href="icons/icon-192.svg" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#070b16;--bg2:#0f1830;--card:rgba(255,255,255,.06);--border:rgba(255,255,255,.14);--text:#e6eeff;--muted:#97a6c9;--pri:#4f84ff;--ok:#10b981;--warn:#f59e0b;--danger:#ef4444}
*{box-sizing:border-box}body{margin:0;font-family:Inter,sans-serif;background:radial-gradient(1200px 800px at 90% -10%,#3b2f94 0%,transparent 40%),radial-gradient(1200px 900px at -20% 10%,#0d3a79 0%,transparent 35%),linear-gradient(150deg,var(--bg) 0%,var(--bg2) 100%);color:var(--text)}
.app{display:grid;grid-template-columns:260px 1fr;min-height:100vh}.sidebar{position:sticky;top:0;height:100vh;background:rgba(10,16,32,.75);backdrop-filter:blur(18px);padding:20px;border-right:1px solid var(--border)}
.brand{font:800 20px Manrope;color:#fff;margin-bottom:20px}.nav a{display:block;padding:12px 14px;color:var(--muted);text-decoration:none;border-radius:12px;margin:6px 0}.nav a.active,.nav a:hover{background:linear-gradient(90deg,rgba(79,132,255,.18),transparent);color:#fff;box-shadow:0 0 0 1px rgba(79,132,255,.35) inset}
.main{padding:20px}.top{position:sticky;top:0;z-index:3;background:rgba(7,11,22,.55);backdrop-filter:blur(10px);border:1px solid var(--border);border-radius:16px;padding:12px 16px;display:flex;gap:12px;align-items:center;justify-content:space-between}.search{flex:1;max-width:420px;background:var(--card);border:1px solid var(--border);padding:10px 12px;border-radius:12px;color:#fff}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:18px 0}.card{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:16px;box-shadow:0 8px 30px rgba(0,0,0,.25)}.card h3{margin:0;color:var(--muted);font-size:12px}.card p{margin:10px 0 0;font-size:28px;font-weight:800}
.panel{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:16px}.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}.filters input,.filters select{background:#0f1a35;border:1px solid var(--border);color:#fff;padding:10px;border-radius:12px}
.table-wrap{overflow:auto}.tbl{width:100%;border-collapse:separate;border-spacing:0 8px}.tbl th{position:sticky;top:0;background:#101a33;color:#bad0ff;text-align:left;padding:12px;font-size:12px}.tbl td{background:rgba(255,255,255,.04);padding:12px}.status{padding:6px 10px;border-radius:999px;font-size:12px}.paid{background:rgba(16,185,129,.2);color:#75f7c0}.pending{background:rgba(239,68,68,.2);color:#ffb3b3}
.progress{height:8px;background:#1f2e58;border-radius:999px;overflow:hidden}.progress>span{display:block;height:100%;background:linear-gradient(90deg,#4f84ff,#6ee7ff)}
.pagination{display:flex;gap:8px;justify-content:center;margin-top:14px}.pagination a{padding:8px 12px;background:#0f1a35;border:1px solid var(--border);border-radius:10px;color:#d9e5ff;text-decoration:none}.install-btn{position:fixed;right:16px;bottom:84px;background:linear-gradient(90deg,#4f84ff,#7c5cff);border:none;color:#fff;padding:12px 14px;border-radius:999px;display:none}
.bottom-nav{display:none}
@media (max-width:1024px){.app{grid-template-columns:80px 1fr}.brand span,.nav a span{display:none}}
@media (max-width:768px){.app{grid-template-columns:1fr}.sidebar{display:none}.grid{grid-template-columns:1fr 1fr}.top{position:sticky;top:8px}.tbl thead{display:none}.tbl tr{display:block;margin:10px 0}.tbl td{display:flex;justify-content:space-between}.bottom-nav{position:fixed;left:10px;right:10px;bottom:10px;display:flex;justify-content:space-around;background:rgba(10,16,32,.8);backdrop-filter:blur(16px);padding:10px;border-radius:16px;border:1px solid var(--border)}}
</style></head><body>
<div class="app"><aside class="sidebar"><div class="brand">🎓 <span>School ERP</span></div><nav class="nav">
<a href="#">Dashboard</a><a href="#">Students</a><a href="#">Fees</a><a class="active" href="#">Monthly Reports</a><a href="#">Ustad Reports</a><a href="#">Analytics</a><a href="#">Settings</a><a href="#">Logout</a>
</nav></aside>
<main class="main"><div class="top"><input class="search" id="search" placeholder="Search students..."><button id="themeToggle">🌗</button><button>🔔<sup>3</sup></button><button>👤 Admin</button></div>
<div class="grid"><div class="card"><h3>Total Students</h3><p><?= $totalRows ?></p></div><div class="card"><h3>Paid Students</h3><p><?= $paidStudents ?></p></div><div class="card"><h3>Pending Students</h3><p><?= $pendingStudents ?></p></div><div class="card"><h3>Total Collection</h3><p>₹<?= number_format($totalMonthlyCollection) ?></p></div><div class="card"><h3>Monthly Fee</h3><p>₹<?= number_format($monthlyFee) ?></p></div><div class="card"><h3>Remaining Amount</h3><p>₹<?= number_format($remainingAmount) ?></p></div></div>
<section class="panel"><form class="filters" method="get"><input type="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>"><select name="limit"><?php foreach($allowedLimits as $l):?><option value="<?= $l ?>" <?= $limit===$l?'selected':''?>><?= $l ?></option><?php endforeach;?></select><select name="class"><option value="ALL">All Classes</option><?php foreach($allClasses as $class):?><option value="<?= htmlspecialchars($class) ?>" <?= $selectedClass===$class?'selected':''?>><?= htmlspecialchars($class) ?></option><?php endforeach;?></select><button type="submit">Apply</button></form>
<div class="table-wrap"><table class="tbl" id="reportTable"><thead><tr><th>#</th><th>Student</th><th>Class</th><th>Fee</th><th>Paid</th><th>Balance</th><th>Progress</th><th>Status</th></tr></thead><tbody>
<?php foreach($monthlyStudents as $i=>$student): $paid=(float)$student['paid_amount'];$balance=max(0,$monthlyFee-$paid);$progress=min(100,($paid/$monthlyFee)*100);$status=$paid>=$monthlyFee?'Paid':'Pending'; ?>
<tr><td><?= $offset+$i+1 ?></td><td><?= htmlspecialchars($student['name']) ?></td><td><?= htmlspecialchars($student['class']) ?></td><td>₹<?= number_format($monthlyFee) ?></td><td>₹<?= number_format($paid) ?></td><td>₹<?= number_format($balance) ?></td><td><div class="progress"><span style="width:<?= round($progress) ?>%"></span></div><?= round($progress) ?>%</td><td><span class="status <?= strtolower($status) ?>"><?= $status ?></span></td></tr>
<?php endforeach; if(empty($monthlyStudents)):?><tr><td colspan="8">No students found</td></tr><?php endif;?>
</tbody></table></div>
<div class="pagination"><?php for($i=1;$i<=$totalPages;$i++):?><a href="?month=<?= urlencode($selectedMonth) ?>&class=<?= urlencode($selectedClass) ?>&limit=<?= $limit ?>&page=<?= $i ?>"><?= $i ?></a><?php endfor;?></div>
</section></main></div>
<button class="install-btn" id="installBtn">⬇ Install App</button><nav class="bottom-nav"><a>🏠</a><a>👨‍🎓</a><a>💳</a><a>📊</a><a>⚙️</a></nav>
<script>
if('serviceWorker' in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register('service-worker.js'));}
let deferredPrompt;const installBtn=document.getElementById('installBtn');window.addEventListener('beforeinstallprompt',(e)=>{e.preventDefault();deferredPrompt=e;installBtn.style.display='block';});installBtn.addEventListener('click',async()=>{if(!deferredPrompt)return;deferredPrompt.prompt();await deferredPrompt.userChoice;deferredPrompt=null;installBtn.style.display='none';});
const q=document.getElementById('search');q?.addEventListener('input',()=>{const t=q.value.toLowerCase();document.querySelectorAll('#reportTable tbody tr').forEach(r=>{r.style.display=r.textContent.toLowerCase().includes(t)?'':'none'})});
</script></body></html>

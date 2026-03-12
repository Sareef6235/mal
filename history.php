<?php
require __DIR__ . '/config/bootstrap.php';

$classes = fetch_classes($pdo);
$students = $pdo->query('SELECT id,name FROM students ORDER BY name')->fetchAll();
$where = [];
$params = [];
if (!empty($_GET['class_id'])) { $where[] = 's.class_id=:class_id'; $params['class_id'] = (int)$_GET['class_id']; }
if (!empty($_GET['student_id'])) { $where[] = 's.id=:student_id'; $params['student_id'] = (int)$_GET['student_id']; }
if (!empty($_GET['date'])) { $where[] = 'p.date=:date'; $params['date'] = $_GET['date']; }
$sql = 'SELECT p.*, s.name student_name, c.name class_name FROM prayers p JOIN students s ON s.id=p.student_id LEFT JOIN classes c ON c.id=s.class_id';
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY p.date DESC, p.id DESC LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
render_header('ഹിസ്റ്ററി');
?>
<section class="card"><h1 class="text-lg font-bold">📜 ഹിസ്റ്ററി</h1>
<form class="mt-3 grid gap-2" method="get">
  <select name="class_id" class="input"><option value="">എല്ലാ ക്ലാസുകളും</option><?php foreach($classes as $c): ?><option value="<?= (int)$c['id'] ?>" <?= ((string)($c['id'])===($_GET['class_id']??''))?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <input type="date" class="input" name="date" value="<?= e($_GET['date']??'') ?>">
  <select name="student_id" class="input"><option value="">All students</option><?php foreach($students as $s): ?><option value="<?= (int)$s['id'] ?>" <?= ((string)($s['id'])===($_GET['student_id']??''))?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select>
  <button class="btn btn-secondary">Filter</button>
</form></section>
<section class="mt-3 space-y-2">
<?php foreach($rows as $r): ?>
  <article class="card text-sm"><div class="flex justify-between"><strong><?= e($r['student_name']) ?></strong><span><?= e($r['class_name'] ?? '-') ?></span></div><p class="text-xs text-slate-600"><?= e($r['date']) ?> • <?= (int)$r['points'] ?> pt</p>
  <div class="mt-2 flex flex-wrap gap-1"><?php foreach(['subah'=>'സുബഹ്','dhuhr'=>'ളുഹ്ർ','asr'=>'അസർ','maghrib'=>'മഗരിബ്','isha'=>'ഇഷാ'] as $k=>$l): ?><span class="rounded-full px-2 py-1 text-[11px] <?= $r[$k] ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600' ?>"><?= $l ?></span><?php endforeach; ?></div></article>
<?php endforeach; if(!$rows): ?><div class="card text-sm text-slate-600">No records found.</div><?php endif; ?>
</section>
<?php render_nav('history'); render_footer(); ?>

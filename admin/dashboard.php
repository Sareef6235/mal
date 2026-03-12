<?php
require __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);
$totalStudents = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalClasses = (int)$pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$totalRecords = (int)$pdo->query('SELECT COUNT(*) FROM prayers')->fetchColumn();
$prayerStats = $pdo->query('SELECT SUM(subah) subah, SUM(dhuhr) dhuhr, SUM(asr) asr, SUM(maghrib) maghrib, SUM(isha) isha FROM prayers')->fetch();
$classPerf = $pdo->query('SELECT c.name, COALESCE(SUM(p.points),0) points FROM classes c LEFT JOIN students s ON s.class_id=c.id LEFT JOIN prayers p ON p.student_id=s.id GROUP BY c.id ORDER BY points DESC')->fetchAll();
render_header('Admin Dashboard');
?>
<section class="card"><div class="flex items-center justify-between"><h1 class="text-xl font-bold">Admin Dashboard</h1><a class="text-sm text-red-600" href="/logout.php">Logout</a></div></section>
<section class="mt-3 grid grid-cols-2 gap-2">
  <article class="card"><p class="text-xs">Total Students</p><p class="text-lg font-bold"><?= $totalStudents ?></p></article>
  <article class="card"><p class="text-xs">Total Classes</p><p class="text-lg font-bold"><?= $totalClasses ?></p></article>
  <article class="card"><p class="text-xs">Total Records</p><p class="text-lg font-bold"><?= $totalRecords ?></p></article>
  <article class="card"><p class="text-xs">Today</p><p class="text-lg font-bold"><?= date('d M') ?></p></article>
</section>
<section class="mt-3 card"><h2 class="font-semibold">Prayer completion chart</h2><div class="mt-2 space-y-1 text-sm"><?php foreach(['subah'=>'സുബഹ്','dhuhr'=>'ളുഹ്ർ','asr'=>'അസർ','maghrib'=>'മഗരിബ്','isha'=>'ഇഷാ'] as $k=>$l): $v=(int)($prayerStats[$k]??0); ?><div class="flex items-center gap-2"><span class="w-16"><?= $l ?></span><div class="h-2 flex-1 rounded bg-slate-200"><div class="h-2 rounded bg-emerald-500" style="width: <?= min(100, $v) ?>%"></div></div><span><?= $v ?></span></div><?php endforeach; ?></div></section>
<section class="mt-3 card"><h2 class="font-semibold">Class performance chart</h2><div class="mt-2 space-y-1 text-sm"><?php foreach($classPerf as $c): ?><div class="flex justify-between"><span><?= e($c['name']) ?></span><strong><?= (int)$c['points'] ?> pt</strong></div><?php endforeach; ?></div></section>
<section class="mt-3 grid grid-cols-2 gap-2 text-sm"><a class="btn btn-secondary text-center" href="/admin/students.php">Students</a><a class="btn btn-secondary text-center" href="/admin/classes.php">Classes</a><a class="btn btn-secondary text-center" href="/questions.php">Questions</a><a class="btn btn-secondary text-center" href="/bulk_upload.php">Bulk Upload</a></section>
<?php render_nav('profile'); render_footer(); ?>

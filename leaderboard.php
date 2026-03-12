<?php
require __DIR__ . '/config/bootstrap.php';
$range = $_GET['range'] ?? 'daily';
$from = date('Y-m-d');
if ($range === 'weekly') $from = date('Y-m-d', strtotime('monday this week'));
if ($range === 'monthly') $from = date('Y-m-01');
$stmt = $pdo->prepare('SELECT s.name, c.name class_name, SUM(p.points) pts FROM prayers p JOIN students s ON s.id=p.student_id LEFT JOIN classes c ON c.id=s.class_id WHERE p.date BETWEEN :f AND :t GROUP BY s.id ORDER BY pts DESC LIMIT 50');
$stmt->execute(['f'=>$from,'t'=>date('Y-m-d')]);
$rows = $stmt->fetchAll();
render_header('Leaderboard');
?>
<section class="card"><h1 class="text-lg font-bold">📊 Leaderboard</h1><div class="mt-2 flex gap-2 text-xs"><a class="btn <?= $range==='daily'?'btn-primary':'btn-secondary' ?>" href="?range=daily">Daily</a><a class="btn <?= $range==='weekly'?'btn-primary':'btn-secondary' ?>" href="?range=weekly">Weekly</a><a class="btn <?= $range==='monthly'?'btn-primary':'btn-secondary' ?>" href="?range=monthly">Monthly</a></div></section>
<section class="mt-3 space-y-2"><?php foreach($rows as $i=>$r): ?><article class="card text-sm flex justify-between"><span><?= $i+1 ?>. <?= e($r['name']) ?> (<?= e($r['class_name']??'-') ?>)</span><strong><?= (int)$r['pts'] ?> pt</strong></article><?php endforeach; if(!$rows): ?><div class="card text-sm">No data.</div><?php endif; ?></section>
<?php render_nav('home'); render_footer(); ?>

<?php
require __DIR__ . '/../config/bootstrap.php';
require_role(['teacher']);
$rows = $pdo->query('SELECT s.name,c.name class_name,MAX(p.date) last_date FROM students s LEFT JOIN classes c ON c.id=s.class_id LEFT JOIN prayers p ON p.student_id=s.id GROUP BY s.id ORDER BY s.name')->fetchAll();
render_header('Teacher Students');
?>
<section class="card"><h1 class="text-lg font-bold">Students Overview</h1></section>
<section class="mt-3 space-y-2"><?php foreach($rows as $r): ?><article class="card text-sm"><div class="flex justify-between"><strong><?= e($r['name']) ?></strong><span><?= e($r['class_name']??'-') ?></span></div><p class="text-xs text-slate-600">Last prayer entry: <?= e($r['last_date'] ?? 'N/A') ?></p></article><?php endforeach; ?></section>
<?php render_nav('profile'); render_footer(); ?>

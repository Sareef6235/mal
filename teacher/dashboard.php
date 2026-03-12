<?php
require __DIR__ . '/../config/bootstrap.php';
require_role(['teacher']);
$students = $pdo->query('SELECT s.name, c.name class_name FROM students s LEFT JOIN classes c ON c.id=s.class_id ORDER BY s.name LIMIT 50')->fetchAll();
render_header('Teacher Dashboard');
?>
<section class="card"><div class="flex justify-between"><h1 class="text-lg font-bold">Teacher Dashboard</h1><a href="/logout.php" class="text-red-600 text-sm">Logout</a></div>
<p class="text-sm text-slate-600 mt-1">View students and latest records.</p></section>
<section class="mt-3 space-y-2"><?php foreach($students as $s): ?><article class="card text-sm flex justify-between"><span><?= e($s['name']) ?></span><span><?= e($s['class_name']??'-') ?></span></article><?php endforeach; ?></section>
<?php render_nav('profile'); render_footer(); ?>

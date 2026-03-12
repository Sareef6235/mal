<?php
require __DIR__ . '/../config/bootstrap.php';
require_role(['student']);
$user = current_user();
$stmt = $pdo->prepare('SELECT id,name FROM students WHERE username=:u LIMIT 1');
$stmt->execute(['u'=>$user['username']]);
$student = $stmt->fetch();
$rows=[];
if($student){$s=$pdo->prepare('SELECT date,points FROM prayers WHERE student_id=:id ORDER BY date DESC LIMIT 10');$s->execute(['id'=>$student['id']]);$rows=$s->fetchAll();}
render_header('Student Dashboard');
?>
<section class="card"><div class="flex justify-between"><h1 class="text-lg font-bold">Welcome <?= e($user['name']) ?></h1><a href="/logout.php" class="text-red-600 text-sm">Logout</a></div><p class="text-xs text-slate-600">Your recent prayer points</p></section>
<section class="mt-3 space-y-2"><?php foreach($rows as $r): ?><article class="card text-sm flex justify-between"><span><?= e($r['date']) ?></span><strong><?= (int)$r['points'] ?> pt</strong></article><?php endforeach; if(!$rows): ?><div class="card text-sm">No data yet.</div><?php endif; ?></section>
<?php render_nav('profile'); render_footer(); ?>

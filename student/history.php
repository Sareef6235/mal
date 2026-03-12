<?php
require __DIR__ . '/../config/bootstrap.php';
require_role(['student']);
$user=current_user();
$stmt=$pdo->prepare('SELECT p.* FROM prayers p JOIN students s ON s.id=p.student_id WHERE s.username=:u ORDER BY p.date DESC');
$stmt->execute(['u'=>$user['username']]);
$rows=$stmt->fetchAll();
render_header('Student History');
?>
<section class="card"><h1 class="text-lg font-bold">My Prayer History</h1></section>
<section class="mt-3 space-y-2"><?php foreach($rows as $r): ?><article class="card text-sm"><div class="flex justify-between"><span><?= e($r['date']) ?></span><strong><?= (int)$r['points'] ?> pt</strong></div></article><?php endforeach; ?></section>
<?php render_nav('history'); render_footer(); ?>

<?php
require __DIR__ . '/../config/bootstrap.php';
require_role(['student']);
$user=current_user();
$stmt=$pdo->prepare('SELECT er.score,e.title FROM exam_results er JOIN exams e ON e.id=er.exam_id JOIN students s ON s.id=er.student_id WHERE s.username=:u ORDER BY er.id DESC');
$stmt->execute(['u'=>$user['username']]);
$rows=$stmt->fetchAll();
render_header('Exam Results');
?>
<section class="card"><h1 class="text-lg font-bold">Exam Results</h1></section>
<section class="mt-3 space-y-2"><?php foreach($rows as $r): ?><article class="card text-sm flex justify-between"><span><?= e($r['title']) ?></span><strong><?= (int)$r['score'] ?></strong></article><?php endforeach; if(!$rows): ?><div class="card text-sm">No results.</div><?php endif; ?></section>
<?php render_nav('profile'); render_footer(); ?>

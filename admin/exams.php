<?php
require __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);
verify_csrf();
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(($_POST['action']??'')==='create'){
    $stmt=$pdo->prepare('INSERT INTO exams(title,total_questions) VALUES(:t,:q)');
    $stmt->execute(['t'=>trim($_POST['title']??''),'q'=>(int)($_POST['total_questions']??0)]);
  }
  header('Location: /admin/exams.php');exit;
}
$exams=$pdo->query('SELECT * FROM exams ORDER BY id DESC')->fetchAll();
render_header('Exams');
?>
<section class="card"><h1 class="text-lg font-bold">Exam System</h1><form method="post" class="mt-2 space-y-2"><?= csrf_input() ?><input type="hidden" name="action" value="create"><input class="input" name="title" placeholder="Exam title" required><input class="input" type="number" name="total_questions" placeholder="Total questions"><button class="btn btn-primary w-full">Create Exam</button></form></section>
<section class="mt-3 space-y-2"><?php foreach($exams as $e): ?><article class="card text-sm flex justify-between"><span><?= e($e['title']) ?></span><span><?= (int)$e['total_questions'] ?> questions</span></article><?php endforeach; ?></section>
<?php render_nav('profile'); render_footer(); ?>

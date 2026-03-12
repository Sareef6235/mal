<?php
require __DIR__ . '/config/bootstrap.php';
require_role(['admin']);
verify_csrf();
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']??'add';
  if($action==='add'){
    $stmt=$pdo->prepare('INSERT INTO questions (question,answer) VALUES (:q,:a)');
    $stmt->execute(['q'=>trim($_POST['question']??''),'a'=>trim($_POST['answer']??'')]);
    flash('ok','Question added');
  }elseif($action==='edit'){
    $stmt=$pdo->prepare('UPDATE questions SET question=:q, answer=:a WHERE id=:id');
    $stmt->execute(['q'=>trim($_POST['question']??''),'a'=>trim($_POST['answer']??''),'id'=>(int)$_POST['id']]);
    flash('ok','Updated');
  }elseif($action==='delete'){
    $stmt=$pdo->prepare('DELETE FROM questions WHERE id=:id');
    $stmt->execute(['id'=>(int)$_POST['id']]);
    flash('ok','Deleted');
  }
  header('Location: /questions.php');exit;
}
$rows=$pdo->query('SELECT * FROM questions ORDER BY id DESC')->fetchAll();
$editId=(int)($_GET['edit']??0);
$edit=null;foreach($rows as $r){if((int)$r['id']===$editId){$edit=$r;break;}}
render_header('Questions');
?>
<section class="card"><h1 class="text-lg font-bold">Questions System</h1><?php if($m=flash('ok')):?><p data-autohide class="text-sm text-emerald-700"><?= e($m) ?></p><?php endif; ?>
<form method="post" class="mt-2 space-y-2"><?= csrf_input() ?>
<input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>"><?php if($edit):?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
<input class="input" name="question" placeholder="Question" value="<?= e($edit['question']??'') ?>" required>
<textarea class="input" name="answer" placeholder="Answer" required><?= e($edit['answer']??'') ?></textarea>
<button class="btn btn-primary w-full"><?= $edit ? 'Update' : 'Add' ?> Question</button>
</form></section>
<section class="mt-3 space-y-2"><?php foreach($rows as $r): ?><article class="card text-sm"><p><strong>Q:</strong> <?= e($r['question']) ?></p><p><strong>A:</strong> <?= e($r['answer']) ?></p><div class="mt-2 flex gap-2"><a class="btn btn-secondary" href="/questions.php?edit=<?= (int)$r['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete?')"><?= csrf_input() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger">Delete</button></form></div></article><?php endforeach; ?></section>
<?php render_nav('profile'); render_footer(); ?>

<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
$db = connect_db($config['db']); ensure_core_tables($db);
$id=(int)($_GET['id']??0); $msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && $id>0){
  $st=$db->prepare('UPDATE students SET full_name=:n,class_name=:c,parent_name=:p,phone=:ph,address=:a WHERE id=:id');
  $st->execute(['id'=>$id,'n'=>trim($_POST['full_name']??''),'c'=>trim($_POST['class_name']??''),'p'=>trim($_POST['parent_name']??''),'ph'=>trim($_POST['phone']??''),'a'=>trim($_POST['address']??'')]);
  $msg='Student updated';
}
$st=$db->prepare('SELECT * FROM students WHERE id=:id');$st->execute(['id'=>$id]);$row=$st->fetch();
include __DIR__ . '/../layout/header.php'; include __DIR__ . '/../layout/sidebar.php';
?>
<h3>Edit Student</h3><p class="text-success"><?= h($msg) ?></p>
<?php if($row): ?><form method="post" class="row g-2">
<div class="col-md-4"><input class="form-control" value="<?= h((string)$row['register_no']) ?>" disabled></div>
<div class="col-md-4"><input class="form-control" name="full_name" value="<?= h((string)$row['full_name']) ?>"></div>
<div class="col-md-4"><input class="form-control" name="class_name" value="<?= h((string)$row['class_name']) ?>"></div>
<div class="col-md-4"><input class="form-control" name="parent_name" value="<?= h((string)$row['parent_name']) ?>"></div>
<div class="col-md-4"><input class="form-control" name="phone" value="<?= h((string)$row['phone']) ?>"></div>
<div class="col-md-4"><input class="form-control" name="address" value="<?= h((string)$row['address']) ?>"></div>
<div class="col-12"><button class="btn btn-primary">Update</button></div></form><?php endif; ?>
<?php include __DIR__ . '/../layout/footer.php';

<?php
declare(strict_types=1);
require __DIR__ . '/../db.php';
$db = connect_db($config['db']); ensure_core_tables($db);
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $photoPath = null;
    if(!empty($_FILES['photo']['tmp_name'])){
        $name = uniqid('stu_', true).'.jpg';
        $dir = __DIR__.'/../photos'; if(!is_dir($dir)) mkdir($dir,0775,true);
        $dest = $dir.'/'.$name;
        if(move_uploaded_file($_FILES['photo']['tmp_name'],$dest)){ $photoPath = 'photos/'.$name; }
    }
    $st=$db->prepare('INSERT INTO students(register_no,full_name,class_name,parent_name,phone,address,photo_path) VALUES(:r,:n,:c,:p,:ph,:a,:pp)');
    $st->execute(['r'=>trim($_POST['register_no']??''),'n'=>trim($_POST['full_name']??''),'c'=>trim($_POST['class_name']??''),'p'=>trim($_POST['parent_name']??''),'ph'=>trim($_POST['phone']??''),'a'=>trim($_POST['address']??''),'pp'=>$photoPath]);
    $msg='Student added';
}
include __DIR__ . '/../layout/header.php'; include __DIR__ . '/../layout/sidebar.php';
?>
<h3>Add Student</h3><p class="text-success"><?= h($msg) ?></p>
<form method="post" enctype="multipart/form-data" class="row g-2">
<div class="col-md-4"><input class="form-control" name="register_no" placeholder="Register No" required></div>
<div class="col-md-4"><input class="form-control" name="full_name" placeholder="Name" required></div>
<div class="col-md-4"><input class="form-control" name="class_name" placeholder="Class" required></div>
<div class="col-md-4"><input class="form-control" name="parent_name" placeholder="Parent Name"></div>
<div class="col-md-4"><input class="form-control" name="phone" placeholder="Phone"></div>
<div class="col-md-4"><input class="form-control" name="address" placeholder="Address"></div>
<div class="col-md-4"><input class="form-control" type="file" name="photo" accept="image/*"></div>
<div class="col-12"><button class="btn btn-primary">Save</button></div></form>
<?php include __DIR__ . '/../layout/footer.php';

<?php require_once 'config.php'; requireLogin(); if(!isSuperAdmin()) die('Unauthorized');
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['name']);$username=trim($_POST['username']);$role=$_POST['role'];$password=password_hash($_POST['password'], PASSWORD_DEFAULT);
  $st=$pdo->prepare('INSERT INTO users(name,username,password,role) VALUES(?,?,?,?)');$st->execute([$name,$username,$password,$role]);$msg='User created';
}
$users=$pdo->query('SELECT id,name,username,role FROM users ORDER BY id DESC')->fetchAll();
include 'header.php'; ?>
<div class="card glass p-3"><h4>Manage Users</h4><?php if($msg):?><div class="alert alert-success"><?=h($msg)?></div><?php endif;?>
<form method="post" class="row g-2 mb-3"><div class="col-md-3"><input class="form-control" name="name" placeholder="Name" required></div><div class="col-md-3"><input class="form-control" name="username" placeholder="Username" required></div><div class="col-md-3"><input class="form-control" type="password" name="password" placeholder="Password" required></div><div class="col-md-2"><select class="form-select" name="role"><option value="teacher">Teacher</option><option value="admin">Admin</option><option value="super_admin">Super Admin</option></select></div><div class="col-md-1"><button class="btn btn-primary">Add</button></div></form>
<table class="table table-dark"><tr><th>ID</th><th>Name</th><th>Username</th><th>Role</th></tr><?php foreach($users as $u):?><tr><td><?=$u['id']?></td><td><?=h($u['name'])?></td><td><?=h($u['username'])?></td><td><?=h($u['role'])?></td></tr><?php endforeach;?></table></div>
<?php include 'footer.php'; ?>

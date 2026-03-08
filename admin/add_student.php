<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_auth('admin');
$message='';
if ($_SERVER['REQUEST_METHOD']==='POST' && verify_csrf($_POST['csrf_token'] ?? null)) {
  $stmt=$pdo->prepare('INSERT INTO students (name,register_no,class) VALUES (?,?,?)');
  $stmt->execute([trim($_POST['name']??''),trim($_POST['register_no']??''),trim($_POST['class']??'')]);
  $message='Student added successfully';
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Add Student</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><?php require __DIR__ . '/../includes/header.php'; ?><div class="container card"><h1>Add Student</h1><?php if($message):?><div class="success"><?= $message ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>"><label>Name</label><input name="name" required><label>Register No</label><input name="register_no" required><label>Class</label><input name="class" required><button>Add</button></form></div></body></html>

<?php
require_once 'config.php'; requireLogin();
if(!isAdmin()){die('Unauthorized');}
$id=(int)($_GET['id'] ?? 0);
if($id>0){$stmt=$pdo->prepare("UPDATE fees SET status='Verified' WHERE id=?");$stmt->execute([$id]);}
header('Location: view_fees.php');

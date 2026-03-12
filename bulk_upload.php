<?php
require __DIR__ . '/config/bootstrap.php';
require_role(['admin']);
verify_csrf();
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['csv'])){
  $fh=fopen($_FILES['csv']['tmp_name'],'r');
  $header=fgetcsv($fh);
  $inserted=0;
  while(($row=fgetcsv($fh))!==false){
    [$studentName,$className,$date,$subah,$dhuhr,$asr,$maghrib,$isha]=array_pad($row,8,'');
    if(!$studentName||!$className||!$date) continue;
    $pdo->beginTransaction();
    $classStmt=$pdo->prepare('INSERT INTO classes(name) VALUES(:n) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
    $classStmt->execute(['n'=>trim($className)]);
    $classId=(int)$pdo->lastInsertId();
    $studentStmt=$pdo->prepare('INSERT INTO students(name,class_id,username,password) VALUES(:n,:c,:u,:p) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), class_id=VALUES(class_id)');
    $uname=strtolower(preg_replace('/\s+/', '', trim($studentName)));
    $studentStmt->execute(['n'=>trim($studentName),'c'=>$classId,'u'=>$uname,'p'=>password_hash('student1',PASSWORD_DEFAULT)]);
    $studentId=(int)$pdo->lastInsertId();
    $s=(int)parse_bool((string)$subah);$d=(int)parse_bool((string)$dhuhr);$a=(int)parse_bool((string)$asr);$m=(int)parse_bool((string)$maghrib);$i=(int)parse_bool((string)$isha);
    $points=$s+$d+$a+$m+$i;
    $pr=$pdo->prepare('INSERT INTO prayers(student_id,date,subah,dhuhr,asr,maghrib,isha,points) VALUES(:sid,:dt,:s,:d,:a,:m,:i,:p) ON DUPLICATE KEY UPDATE subah=VALUES(subah),dhuhr=VALUES(dhuhr),asr=VALUES(asr),maghrib=VALUES(maghrib),isha=VALUES(isha),points=VALUES(points)');
    $pr->execute(['sid'=>$studentId,'dt'=>$date,'s'=>$s,'d'=>$d,'a'=>$a,'m'=>$m,'i'=>$i,'p'=>$points]);
    $pdo->commit();$inserted++;
  }
  fclose($fh);
  flash('ok',"Imported {$inserted} rows");
  header('Location: /bulk_upload.php');exit;
}
render_header('Bulk Upload');
?>
<section class="card"><h1 class="text-lg font-bold">CSV Bulk Upload</h1><p class="text-xs text-slate-600">student_name,class,date,subah,dhuhr,asr,maghrib,isha</p><?php if($m=flash('ok')):?><p data-autohide class="text-sm text-emerald-700 mt-2"><?= e($m) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="mt-3 space-y-2"><?= csrf_input() ?><input type="file" name="csv" accept=".csv" class="input" required><button class="btn btn-primary w-full">Import</button></form></section>
<?php render_nav('profile'); render_footer(); ?>

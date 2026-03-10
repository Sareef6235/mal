<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/bootstrap.php';
$db = db_connect($config['db']);
if (!$db instanceof PDO) die('Database connection failed');

$message=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $madrasa = trim((string)($_POST['madrasa_name'] ?? ''));
    $name = trim((string)($_POST['student_name'] ?? ''));
    $guardian = trim((string)($_POST['guardian_name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $class = trim((string)($_POST['class_name'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    if ($madrasa===''||$name===''||$guardian===''||$phone===''||$class===''||$address==='') {
        $error='Please fill all required fields.';
    } else {
        $photoPath = null;
        if (!empty($_FILES['photo']['name'])) {
            [$ok,$msg] = validate_image_upload($_FILES['photo'], 500*1024);
            if (!$ok) $error = $msg;
            else {
                $dir = ensure_photo_directory();
                $fn = 'admission_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
                $target = $dir . '/' . $fn;
                if (!move_uploaded_file((string)$_FILES['photo']['tmp_name'], $target)) $error='Photo save failed.';
                else $photoPath = 'photos/' . $fn;
            }
        }
        if ($error==='') {
            $st = $db->prepare('INSERT INTO admissions(madrasa_name,student_name,guardian_name,phone,class_name,address,photo_path,status) VALUES(?,?,?,?,?,?,?,?)');
            $st->execute([$madrasa,$name,$guardian,$phone,$class,$address,$photoPath,'Pending']);
            $message='Admission submitted successfully.';
        }
    }
}
$rows = $db->query('SELECT * FROM admissions ORDER BY id DESC LIMIT 100')->fetchAll();
render_header('Online Admission Form');
?>
<div class="card">
  <?php if($message): ?><p class="msg-ok"><?= e($message) ?></p><?php endif; ?>
  <?php if($error): ?><p class="msg-bad"><?= e($error) ?></p><?php endif; ?>
  <form method="post" enctype="multipart/form-data" style="display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
    <input name="madrasa_name" placeholder="Madrasa Name" required>
    <input name="student_name" placeholder="Student Name" required>
    <input name="guardian_name" placeholder="Guardian Name" required>
    <input name="phone" placeholder="Phone" required>
    <input name="class_name" placeholder="Class" required>
    <input name="address" placeholder="Address" required>
    <input type="file" name="photo" accept="image/*">
    <button type="submit">Submit Admission</button>
  </form>
</div>
<div class="card" style="overflow:auto">
  <h3>Recent Admissions</h3>
  <table><thead><tr><th>ID</th><th>Madrasa</th><th>Name</th><th>Guardian</th><th>Class</th><th>Status</th></tr></thead><tbody>
  <?php foreach($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= e((string)$r['madrasa_name']) ?></td><td><?= e((string)$r['student_name']) ?></td><td><?= e((string)$r['guardian_name']) ?></td><td><?= e((string)$r['class_name']) ?></td><td><?= e((string)$r['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table>
</div>
<?php render_footer();

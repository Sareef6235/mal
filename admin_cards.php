<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$db = db_connect($config['db']);
if (!$db instanceof PDO) die('Database connection failed');
$message='';$error='';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    $registerNo = trim((string) ($_POST['register_no'] ?? ''));
    if ($registerNo === '' || !isset($_FILES['photo'])) $error = 'Register number and photo are required.';
    else {
        [$ok, $upMsg] = validate_image_upload($_FILES['photo']);
        if (!$ok) $error = $upMsg;
        else {
            ensure_photo_directory();
            $target = photo_upload_path($registerNo);
            if (move_uploaded_file((string)$_FILES['photo']['tmp_name'], $target)) $message='Photo uploaded successfully.';
            else $error='Could not save uploaded photo.';
        }
    }
}
$className = trim((string) ($_GET['class_name'] ?? ''));
$students = fetch_students_for_id_cards($db, $className, 1000);
render_header('ID Card Admin Panel');
?>
<style>.inner-card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 8px 20px rgba(0,0,0,.08);margin:12px 0;color:#0f172a}.rowx{display:grid;grid-template-columns:1fr 1fr auto;gap:10px}.table{width:100%;border-collapse:collapse}.table th,.table td{padding:8px;border-bottom:1px solid #e2e8f0;text-align:left}.msg{color:#166534}.err{color:#b91c1c}.actions a{margin-right:8px}.btn-link{display:inline-block;padding:8px 10px;background:#1d4ed8;color:#fff;border-radius:8px;text-decoration:none;margin-right:8px}@media (max-width:900px){.rowx{grid-template-columns:1fr}}</style>
<div class="inner-card"><h2>ID Card Admin Panel</h2><p class="small" style="color:#334155">Photo upload size must be below 200KB.</p><?php if ($message): ?><p class="msg"><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="rowx"><input name="register_no" placeholder="Register No" required><input type="file" name="photo" accept="image/*" required><button name="upload_photo" value="1" type="submit">Upload Photo</button></form></div>
<div class="inner-card"><a class="btn-link" href="self_card.php">Self Card Create / Download Page</a></div>
<div class="inner-card"><form method="get" class="rowx" style="grid-template-columns:1fr auto auto"><input name="class_name" placeholder="Filter class (optional)" value="<?= e($className) ?>"><button type="submit">Generate Card List</button><button type="button" onclick="window.location.href='idcard_bulk.php?class_name=<?= rawurlencode($className) ?>'">Open A4 (8/page)</button></form></div>
<div class="inner-card"><h3>Students (<?= count($students) ?>)</h3><table class="table"><thead><tr><th>Name</th><th>Register No</th><th>Class</th><th>Actions</th></tr></thead><tbody><?php foreach ($students as $s): ?><tr><td><?= e((string)$s['name']) ?></td><td><?= e((string)$s['register_no']) ?></td><td><?= e((string)$s['class_name']) ?></td><td class="actions"><a href="idcard.php?register_no=<?= rawurlencode((string)$s['register_no']) ?>" target="_blank">Generate Card</a><a href="idcard_bulk.php?class_name=<?= rawurlencode((string)$s['class_name']) ?>" target="_blank">A4 Print</a><a href="idcard_edit.php?register_no=<?= rawurlencode((string)$s['register_no']) ?>" target="_blank">Edit ID Card</a></td></tr><?php endforeach; ?></tbody></table></div>
<?php render_footer();

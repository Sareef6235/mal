<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$db = db_connect($config['db']);
if (!$db instanceof PDO) {
    die('Database connection failed');
}

$reg = trim((string) ($_GET['register_no'] ?? $_POST['register_no'] ?? ''));
if ($reg === '') {
    die('Please provide register_no');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_card'])) {
    $newName = trim((string) ($_POST['name'] ?? ''));
    $newClass = trim((string) ($_POST['class_name'] ?? ''));
    $newReg = trim((string) ($_POST['register_no_new'] ?? ''));

    if ($newName === '' || $newClass === '' || $newReg === '') {
        $error = 'Name, class and register no are required.';
    }

    $hasPhoto = isset($_FILES['photo']) && (int) ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($error === '' && $hasPhoto) {
        [$okUpload, $uploadMessage] = validate_image_upload($_FILES['photo']);
        if (!$okUpload) {
            $error = $uploadMessage;
        }
    }

    if ($error === '') {
        $up = $db->prepare('UPDATE students SET full_name=:n, class_name=:c, register_no=:newr WHERE register_no=:oldr');
        $up->execute(['n' => $newName, 'c' => $newClass, 'newr' => $newReg, 'oldr' => $reg]);

        if ($hasPhoto) {
            ensure_photo_directory();
            move_uploaded_file((string) $_FILES['photo']['tmp_name'], photo_upload_path($newReg));
        }

        $reg = $newReg;
        $message = 'ID card data updated successfully.';
    }
}

$st = $db->prepare('SELECT id, full_name AS name, register_no, class_name FROM students WHERE register_no=:r LIMIT 1');
$st->execute(['r' => $reg]);
$student = $st->fetch();
if (!$student) {
    die('Student not found');
}

render_header('Edit ID Card');
?>
<style>
.inner-card{background:#fff;border-radius:14px;padding:18px;box-shadow:0 10px 24px rgba(2,6,23,.12);margin:12px 0;color:#0f172a}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.msg{color:#166534}.err{color:#b91c1c}
@media (max-width:900px){.grid{grid-template-columns:1fr}}
</style>
<div class="inner-card">
    <h2>Edit ID Card</h2>
    <p class="small" style="color:#334155">Photo upload size must be below 200KB.</p>
    <?php if ($message): ?><p class="msg"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="grid">
        <input type="hidden" name="register_no" value="<?= e((string) $student['register_no']) ?>">
        <input name="name" value="<?= e((string) $student['name']) ?>" placeholder="Name" required>
        <input name="class_name" value="<?= e((string) $student['class_name']) ?>" placeholder="Class" required>
        <input name="register_no_new" value="<?= e((string) $student['register_no']) ?>" placeholder="Register No" required>
        <input type="file" name="photo" accept="image/*">
        <button type="submit" name="save_card" value="1">Save Changes</button>
    </form>

    <p style="margin-top:12px">
        <a href="idcard.php?register_no=<?= rawurlencode((string) $student['register_no']) ?>" target="_blank">Generate Updated Card</a>
        |
        <a href="idcard.php?register_no=<?= rawurlencode((string) $student['register_no']) ?>" target="_blank" onclick="window.print()">Download PDF</a>
    </p>
</div>
<?php render_footer(); ?>

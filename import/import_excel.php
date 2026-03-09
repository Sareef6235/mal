<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$message = '';

if (isset($_POST['upload']) && !empty($_FILES['file']['tmp_name'])) {
    $file = $_FILES['file']['tmp_name'];
    $handle = fopen($file, 'r');
    if ($handle !== false) {
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $reg = trim((string)($row[0] ?? ''));
            $name = trim((string)($row[1] ?? ''));
            $subject1 = (float)($row[2] ?? 0);
            $subject2 = (float)($row[3] ?? 0);
            $subject3 = (float)($row[4] ?? 0);
            if ($reg === '' || $name === '') continue;

            $sql = db_driver($db) === 'sqlite'
                ? 'INSERT INTO students (register_no, register_number, full_name, name, class_name) VALUES (:r,:r,:n,:n,:c) ON CONFLICT(register_no) DO UPDATE SET full_name=:n,name=:n'
                : 'INSERT INTO students (register_no, register_number, full_name, name, class_name) VALUES (:r,:r,:n,:n,:c) ON DUPLICATE KEY UPDATE full_name=:n,name=:n';
            $db->prepare($sql)->execute(['r' => $reg, 'n' => $name, 'c' => 'General']);

            $stmt = $db->prepare('SELECT id FROM students WHERE COALESCE(register_no, register_number) = ? LIMIT 1');
            $stmt->execute([$reg]);
            $studentId = (int)$stmt->fetchColumn();
            if ($studentId <= 0) continue;

            $examId = 1;
            ensure_exam($db, $examId);
            ensure_subject($db, 1, 'quran', 'Quran');
            ensure_subject($db, 2, 'fiqh', 'Fiqh');
            ensure_subject($db, 3, 'arabic', 'Arabic');
            insert_mark($db, $examId, $studentId, 1, $subject1);
            insert_mark($db, $examId, $studentId, 2, $subject2);
            insert_mark($db, $examId, $studentId, 3, $subject3);
        }
        fclose($handle);
        $message = 'Import Completed';
    }
}
?>
<!DOCTYPE html>
<html><head><title>Import Excel</title></head><body>
<h2>Upload Result File</h2>
<form method="post" enctype="multipart/form-data">
<input type="file" name="file" required>
<button name="upload">Upload</button>
</form>
<p><?= e($message) ?></p>
</body></html>

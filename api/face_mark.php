<?php

declare(strict_types=1);

require_once __DIR__ . '/_helpers.php';

$registerNo = trim($_POST['register_no'] ?? '');
if ($registerNo === '') {
    json_response(['success' => false, 'message' => 'Register number is required.'], 422);
}

$stmt = $pdo->prepare('SELECT id, name FROM students WHERE register_no = ? LIMIT 1');
$stmt->execute([$registerNo]);
$student = $stmt->fetch();

if (!$student) {
    json_response(['success' => false, 'message' => 'Student not found.'], 404);
}

$photoPath = null;
if (!empty($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
    $mime = mime_content_type($_FILES['photo']['tmp_name']) ?: '';
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
        json_response(['success' => false, 'message' => 'Invalid image format.'], 422);
    }
    $photoPath = '../uploads/attendance/' . uniqid('face_', true) . '.jpg';
    move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/' . $photoPath);
}

mark_attendance($pdo, (int) $student['id'], 'face', $photoPath);
json_response(['success' => true, 'message' => 'Face attendance marked for ' . $student['name']]);

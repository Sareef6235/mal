<?php

declare(strict_types=1);

require_once __DIR__ . '/_helpers.php';

$studentId = (int) ($_POST['student_id'] ?? 0);
if ($studentId < 1 || empty($_FILES['face_image']['tmp_name'])) {
    json_response(['success' => false, 'message' => 'Student ID and face image required.'], 422);
}

$mime = mime_content_type($_FILES['face_image']['tmp_name']) ?: '';
if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
    json_response(['success' => false, 'message' => 'Only JPG/PNG allowed.'], 422);
}

$target = '../uploads/faces/' . uniqid('student_' . $studentId . '_', true) . '.jpg';
move_uploaded_file($_FILES['face_image']['tmp_name'], __DIR__ . '/' . $target);

$stmt = $pdo->prepare('UPDATE students SET face_image = ? WHERE id = ?');
$stmt->execute([$target, $studentId]);
json_response(['success' => true, 'message' => 'Face registered successfully.']);

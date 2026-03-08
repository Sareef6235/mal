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

mark_attendance($pdo, (int) $student['id'], 'qr');
json_response(['success' => true, 'message' => 'Attendance marked for ' . $student['name']]);

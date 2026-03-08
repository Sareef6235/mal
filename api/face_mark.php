<?php

declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$studentId = (int) ($_POST['student_id'] ?? 0);
$registerNo = trim($_POST['register_no'] ?? '');

if ($studentId <= 0 && $registerNo === '') {
    echo json_encode([
        'ok' => false,
        'message' => 'student_id or register_no required',
    ]);
    exit;
}

if ($studentId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, register_no FROM students WHERE id = ? LIMIT 1');
    $stmt->execute([$studentId]);
} else {
    $stmt = $pdo->prepare('SELECT id, name, register_no FROM students WHERE register_no = ? LIMIT 1');
    $stmt->execute([$registerNo]);
}

$student = $stmt->fetch();
if (!$student) {
    echo json_encode([
        'ok' => false,
        'message' => 'Student not found',
    ]);
    exit;
}

if ($registerNo !== '' && strcasecmp((string) $student['register_no'], $registerNo) !== 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'QR/Register mismatch for selected student',
    ]);
    exit;
}

$studentId = (int) $student['id'];
$date = date('Y-m-d');
$time = date('H:i:s');

$check = $pdo->prepare('SELECT id FROM attendance WHERE student_id = ? AND date = ?');
$check->execute([$studentId, $date]);

if ($check->fetch()) {
    echo json_encode([
        'ok' => true,
        'message' => 'Already marked',
    ]);
    exit;
}

$insert = $pdo->prepare('INSERT INTO attendance(student_id, date, time, method) VALUES(?, ?, ?, ?)');
$insert->execute([$studentId, $date, $time, 'face']);

echo json_encode([
    'ok' => true,
    'message' => 'Face attendance marked for ' . $student['name'],
]);

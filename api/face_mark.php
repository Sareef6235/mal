<?php

declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$studentId = (int) ($_POST['student_id'] ?? 0);

if ($studentId <= 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'Student ID required',
    ]);
    exit;
}

$studentStmt = $pdo->prepare('SELECT id, name FROM students WHERE id = ? LIMIT 1');
$studentStmt->execute([$studentId]);
$student = $studentStmt->fetch();

if (!$student) {
    echo json_encode([
        'ok' => false,
        'message' => 'Student not found',
    ]);
    exit;
}

$date = date('Y-m-d');
$time = date('H:i:s');

$check = $pdo->prepare('SELECT id FROM attendance WHERE student_id=? AND date=?');
$check->execute([$studentId, $date]);

if ($check->fetch()) {
    echo json_encode([
        'ok' => true,
        'message' => 'Already marked',
    ]);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO attendance(student_id,date,time,method) VALUES(?,?,?,?)');
$stmt->execute([
    $studentId,
    $date,
    $time,
    'face',
]);

echo json_encode([
    'ok' => true,
    'message' => 'Face attendance marked for ' . $student['name'],
]);

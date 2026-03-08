<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

$register = trim($_POST['register_no'] ?? '');

if ($register === '') {
    echo json_encode([
        'ok' => false,
        'message' => 'Register number required',
    ]);
    exit;
}

$stmt = $pdo->prepare('SELECT id,name FROM students WHERE register_no=?');
$stmt->execute([$register]);
$student = $stmt->fetch();

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
$check->execute([$student['id'], $date]);

if ($check->fetch()) {
    echo json_encode([
        'ok' => true,
        'message' => 'Already marked',
    ]);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO attendance(student_id,date,time,method) VALUES(?,?,?,?)');
$stmt->execute([
    $student['id'],
    $date,
    $time,
    'qr',
]);

echo json_encode([
    'ok' => true,
    'message' => 'Attendance marked for ' . $student['name'],
]);

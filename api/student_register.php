<?php

declare(strict_types=1);

require_once __DIR__ . '/_helpers.php';

$name = trim($_POST['name'] ?? '');
$registerNo = trim($_POST['register_no'] ?? '');
$class = trim($_POST['class'] ?? '');

if ($name === '' || $registerNo === '' || $class === '') {
    json_response(['success' => false, 'message' => 'All fields are required.'], 422);
}

$stmt = $pdo->prepare('INSERT INTO students (name, register_no, class) VALUES (?, ?, ?)');
$stmt->execute([$name, $registerNo, $class]);
json_response(['success' => true, 'message' => 'Student registered successfully.']);

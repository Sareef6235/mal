<?php

declare(strict_types=1);

require_once __DIR__ . '/_helpers.php';

$students = $pdo->query('SELECT id, name, register_no, class FROM students ORDER BY name')->fetchAll();
json_response(['success' => true, 'students' => $students]);

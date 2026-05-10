<?php
require_once __DIR__ . '/../functions.php';

$materials = material_query($pdo, [
    'q' => trim($_GET['q'] ?? ''),
    'subject' => trim($_GET['subject'] ?? ''),
    'class' => trim($_GET['class'] ?? ''),
    'sort' => trim($_GET['sort'] ?? 'newest'),
    'page' => (int)($_GET['page'] ?? 1),
]);

foreach ($materials as &$material) {
    $material['title'] = e($material['title']);
    $material['description'] = e(mb_strimwidth((string)$material['description'], 0, 110, '...'));
    $material['subject_name'] = e($material['subject_name']);
    $material['class_name'] = e($material['class_name']);
    $material['created_at'] = date('M j', strtotime($material['created_at']));
    $material['icon'] = icon_for_type($material['file_type']);
}

json_response(['materials' => $materials]);

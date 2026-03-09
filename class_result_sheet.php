<?php
declare(strict_types=1);
require_once __DIR__ . '/system.php';
$class = trim((string)($_GET['class'] ?? ''));
$students = $class !== '' ? sys_ranked_students($class) : [];
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"><title><?= e($class ?: 'Class') ?> Results</title></head><body class="bg-light"><main class="container py-4"><div class="card p-3 shadow-sm"><h1>Class <?= e($class ?: '-') ?> Result Sheet</h1><table class="table table-striped"><tr><th>Rank</th><th>Register</th><th>Name</th><th>Total</th></tr><?php foreach($students as $s): ?><tr><td><?= e((string)$s['rank_position']) ?></td><td><?= e((string)$s['register_no']) ?></td><td><?= e((string)$s['name']) ?></td><td><?= e((string)$s['total']) ?></td></tr><?php endforeach; ?></table><div><a class="btn btn-secondary" href="admin_portal.php">Back</a></div></div></main></body></html>

<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$stmt = $pdo->query(
    'SELECT u.name, u.class, r.scored_marks, r.total_marks,
            CASE WHEN r.total_marks > 0 THEN (r.scored_marks / r.total_marks) * 100 ELSE 0 END AS percent
     FROM results r
     INNER JOIN users u ON u.id = r.user_id
     ORDER BY percent DESC, r.scored_marks DESC
     LIMIT 10'
);

$rows = $stmt->fetchAll();

echo json_encode(['success' => true, 'rows' => $rows]);

<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function mark_attendance(PDO $pdo, int $studentId, string $method, ?string $photo = null): bool
{
    $stmt = $pdo->prepare('INSERT INTO attendance (student_id, date, time, method, photo) VALUES (?, CURDATE(), CURTIME(), ?, ?)');
    return $stmt->execute([$studentId, $method, $photo]);
}

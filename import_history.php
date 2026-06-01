<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
$page = max(1, (int)($_GET['page'] ?? 1));
$q = trim((string)($_GET['q'] ?? ''));
$limit = 10;
$offset = ($page - 1) * $limit;
$where = $q === '' ? '1=1' : 'original_name LIKE ?';
$params = $q === '' ? [] : ['%' . $q . '%'];
$countStmt = db()->prepare("SELECT COUNT(*) FROM ocr_imports WHERE $where");
$countStmt->execute($params);
$stmt = db()->prepare("SELECT id, original_name, file_size, rows_found, rows_ready, rows_failed, status, created_at FROM ocr_imports WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
json_response(['success' => true, 'rows' => $stmt->fetchAll(), 'total' => (int)$countStmt->fetchColumn(), 'page' => $page]);

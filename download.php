<?php
require_once __DIR__ . '/functions.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM materials WHERE id=? AND is_published=1');
$stmt->execute([$id]);
$material = $stmt->fetch();
if (!$material) { http_response_code(404); exit('Material not found.'); }
$absolute = __DIR__ . '/' . $material['file_path'];
if (!is_file($absolute)) { http_response_code(404); exit('File missing.'); }
$pdo->prepare('UPDATE materials SET downloads_count = downloads_count + 1 WHERE id=?')->execute([$id]);
$pdo->prepare('INSERT INTO downloads (material_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)')->execute([$id, $_SESSION['user_id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
header('Content-Type: ' . $material['mime_type']);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $material['original_file_name']) . '"');
header('Content-Length: ' . filesize($absolute));
readfile($absolute);
exit;

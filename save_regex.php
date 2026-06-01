<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_csrf();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = (int)($input['id'] ?? 0);
$name = trim((string)($input['name'] ?? ''));
$pattern = trim((string)($input['pattern'] ?? ''));
$flags = trim((string)($input['flags'] ?? 'miu')) ?: 'miu';
if ($name === '' || $pattern === '') json_response(['success' => false, 'message' => 'Name and pattern are required.'], 422);
try { validate_regex($pattern, $flags); } catch (Throwable $e) { json_response(['success' => false, 'message' => $e->getMessage()], 422); }
$data = [$name, $input['description'] ?? '', $pattern, $flags, (int)($input['priority'] ?? 100), !empty($input['is_active']) ? 1 : 0, !empty($input['is_default']) ? 1 : 0, $input['sample_text'] ?? ''];
if (!empty($input['is_default'])) db()->exec('UPDATE regex_rules SET is_default = 0');
if ($id > 0) {
    $data[] = $id;
    db()->prepare('UPDATE regex_rules SET name=?, description=?, pattern=?, flags=?, priority=?, is_active=?, is_default=?, sample_text=? WHERE id=?')->execute($data);
} else {
    db()->prepare('INSERT INTO regex_rules (name, description, pattern, flags, priority, is_active, is_default, sample_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute($data);
    $id = (int)db()->lastInsertId();
}
json_response(['success' => true, 'id' => $id]);

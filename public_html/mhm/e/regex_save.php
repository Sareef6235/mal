<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
verify_csrf();

$action = (string) ($_POST['action'] ?? 'save');

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id < 1) {
        json_response(['success' => false, 'message' => 'Invalid rule id.'], 422);
    }
    $stmt = db()->prepare('DELETE FROM regex_rules WHERE id = :id');
    $stmt->execute([':id' => $id]);
    json_response(['success' => true, 'message' => 'Regex rule deleted.']);
}

if ($action === 'test') {
    $pattern = trim((string) ($_POST['pattern'] ?? ''));
    $replacement = (string) ($_POST['replacement'] ?? '');
    $flags = (string) ($_POST['flags'] ?? 'u');
    $sample = (string) ($_POST['sample'] ?? '');
    if ($pattern === '') {
        json_response(['success' => false, 'message' => 'Pattern is required.'], 422);
    }
    $regex = wrap_pattern($pattern, $flags);
    $count = 0;
    $result = @preg_replace($regex, $replacement, $sample, -1, $count);
    if ($result === null) {
        json_response(['success' => false, 'message' => 'Invalid regex pattern.'], 422);
    }
    json_response(['success' => true, 'result' => $result, 'count' => $count]);
}

$name = trim((string) ($_POST['name'] ?? ''));
$pattern = trim((string) ($_POST['pattern'] ?? ''));
$replacement = (string) ($_POST['replacement'] ?? '');
$flags = trim((string) ($_POST['flags'] ?? 'u')) ?: 'u';
$sort = (int) ($_POST['sort_order'] ?? 100);
$enabled = isset($_POST['enabled']) ? 1 : 0;
$id = (int) ($_POST['id'] ?? 0);

if ($name === '' || $pattern === '') {
    json_response(['success' => false, 'message' => 'Rule name and pattern are required.'], 422);
}

if (@preg_match(wrap_pattern($pattern, $flags), '') === false) {
    json_response(['success' => false, 'message' => 'Invalid regex pattern.'], 422);
}

if ($id > 0) {
    $stmt = db()->prepare('UPDATE regex_rules SET name = :name, pattern = :pattern, replacement = :replacement, flags = :flags, enabled = :enabled, sort_order = :sort WHERE id = :id');
    $stmt->execute([':name' => $name, ':pattern' => $pattern, ':replacement' => $replacement, ':flags' => $flags, ':enabled' => $enabled, ':sort' => $sort, ':id' => $id]);
    json_response(['success' => true, 'message' => 'Regex rule updated.']);
}

$stmt = db()->prepare('INSERT INTO regex_rules (name, pattern, replacement, flags, enabled, sort_order) VALUES (:name, :pattern, :replacement, :flags, :enabled, :sort)');
$stmt->execute([':name' => $name, ':pattern' => $pattern, ':replacement' => $replacement, ':flags' => $flags, ':enabled' => $enabled, ':sort' => $sort]);
json_response(['success' => true, 'message' => 'Regex rule created.']);

<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'preview';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

if ($action === 'preview') {
    $text = (string) ($_POST['text'] ?? '');
    $result = apply_regex_rules($text);
    json_response(['success' => true, 'cleaned_text' => $result['text'], 'applied_rules' => $result['applied']]);
}

if ($action === 'history') {
    json_response(['success' => true, 'history' => recent_history(20)]);
}

if ($action === 'download') {
    $text = (string) ($_POST['text'] ?? '');
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="extracted-text-' . date('Ymd-His') . '.txt"');
    echo $text;
    exit;
}

json_response(['success' => false, 'message' => 'Unknown action.'], 404);

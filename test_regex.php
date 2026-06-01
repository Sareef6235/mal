<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_csrf();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$text = (string)($input['text'] ?? '');
$pattern = (string)($input['pattern'] ?? '');
$flags = (string)($input['flags'] ?? 'miu');
try {
    $regex = validate_regex($pattern, $flags);
    preg_match_all($regex, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);
    $result = [];
    foreach ($matches as $m) {
        $groups = [];
        foreach ($m as $key => $value) {
            if (is_string($key)) $groups[$key] = $value[0];
        }
        $result[] = ['match' => $m[0][0], 'offset' => $m[0][1], 'groups' => $groups];
    }
    json_response(['success' => true, 'count' => count($result), 'matches' => $result]);
} catch (Throwable $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 422);
}

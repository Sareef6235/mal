<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_csrf();
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = (string)($input['action'] ?? 'parse');
$importId = (int)($input['import_id'] ?? 0);
if ($action === 'parse') {
    $stmt = db()->prepare('SELECT * FROM ocr_imports WHERE id = ?');
    $stmt->execute([$importId]);
    $import = $stmt->fetch();
    if (!$import) json_response(['success' => false, 'message' => 'Import not found.'], 404);
    $text = isset($input['text']) ? normalize_ocr_text((string)$input['text']) : (string)($import['extracted_text'] ?? '');
    if (isset($input['text'])) {
        db()->prepare('UPDATE ocr_imports SET extracted_text = ? WHERE id = ?')->execute([$text, $importId]);
    }
    $rules = [];
    if (!empty($input['regex_rule_id'])) {
        $stmt = db()->prepare('SELECT * FROM regex_rules WHERE id = ? AND is_active = 1');
        $stmt->execute([(int)$input['regex_rule_id']]);
        $rules = array_filter([$stmt->fetch()]);
    } else {
        $rules = db()->query('SELECT * FROM regex_rules WHERE is_active = 1 ORDER BY is_default DESC, priority ASC')->fetchAll();
    }
    $rows = [];
    $usedRule = null;
    foreach ($rules as $rule) {
        $rows = array_merge($rows, parse_rows_with_rule($text, $rule));
        $usedRule = $usedRule ?: $rule;
        if (!get_setting('multi_regex_processing', true) && $rows) break;
    }
    db()->beginTransaction();
    db()->prepare('DELETE FROM ocr_import_rows WHERE import_id = ?')->execute([$importId]);
    $ins = db()->prepare('INSERT INTO ocr_import_rows (import_id,row_index,month,class_name,week,total_period,subject,lesson_name,lesson_details,activities,smart_date,exam_date,raw_match,validation_errors,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $ready = $failed = 0;
    foreach ($rows as $row) {
        $errors = [];
        foreach (['month','class_name','subject','lesson_name'] as $required) if (trim((string)$row[$required]) === '') $errors[] = $required . ' is required';
        $status = $errors ? 'failed' : 'ready';
        $status === 'ready' ? $ready++ : $failed++;
        $ins->execute([$importId,$row['row_index'],$row['month'],$row['class_name'],$row['week'],$row['total_period'],$row['subject'],$row['lesson_name'],$row['lesson_details'],$row['activities'],$row['smart_date'],$row['exam_date'],$row['raw_match'],json_encode($errors),$status]);
        $row['id'] = (int)db()->lastInsertId();
        $row['status'] = $status;
        $row['validation_errors'] = $errors;
    }
    db()->prepare('UPDATE ocr_imports SET regex_rule_id=?, rows_found=?, rows_ready=?, rows_failed=?, status=? WHERE id=?')->execute([$usedRule['id'] ?? null, count($rows), $ready, $failed, 'parsed', $importId]);
    db()->commit();
    json_response(['success' => true, 'rows' => $rows, 'summary' => ['found' => count($rows), 'ready' => $ready, 'failed' => $failed]]);
}
if ($action === 'save_draft' || $action === 'import_all') {
    $rows = $input['rows'] ?? [];
    db()->beginTransaction();
    db()->prepare('DELETE FROM ocr_import_rows WHERE import_id = ?')->execute([$importId]);
    $rowStmt = db()->prepare('INSERT INTO ocr_import_rows (import_id,row_index,month,class_name,week,total_period,subject,lesson_name,lesson_details,activities,smart_date,exam_date,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $planStmt = db()->prepare('INSERT INTO monthly_plan (import_row_id,month,class_name,week,total_period,subject,lesson_name,lesson_details,activities,smart_date,exam_date) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $count = 0;
    foreach ($rows as $i => $r) {
        $status = $action === 'import_all' ? 'imported' : 'draft';
        $rowStmt->execute([$importId,$i+1,$r['month'] ?? '',$r['class_name'] ?? '',$r['week'] ?? '',$r['total_period'] ?? '',$r['subject'] ?? '',$r['lesson_name'] ?? '',$r['lesson_details'] ?? '',$r['activities'] ?? '',normalize_date($r['smart_date'] ?? null),normalize_date($r['exam_date'] ?? null),$status]);
        $rowId = (int)db()->lastInsertId();
        if ($action === 'import_all') {
            $planStmt->execute([$rowId,$r['month'] ?? '',$r['class_name'] ?? '',$r['week'] ?? '',$r['total_period'] ?? '',$r['subject'] ?? '',$r['lesson_name'] ?? '',$r['lesson_details'] ?? '',$r['activities'] ?? '',normalize_date($r['smart_date'] ?? null),normalize_date($r['exam_date'] ?? null)]);
        }
        $count++;
    }
    db()->prepare('UPDATE ocr_imports SET rows_found=?, rows_ready=?, rows_failed=0, status=? WHERE id=?')->execute([$count, $count, $action === 'import_all' ? 'imported' : 'draft', $importId]);
    db()->commit();
    json_response(['success' => true, 'rows' => $count]);
}
if ($action === 'cancel') {
    db()->prepare('UPDATE ocr_imports SET status=? WHERE id=?')->execute(['cancelled', $importId]);
    json_response(['success' => true]);
}
json_response(['success' => false, 'message' => 'Unknown action.'], 400);

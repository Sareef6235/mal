<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function db(): PDO {
    $pdo = db_connect($GLOBALS['config']['db']);
    if (!$pdo) throw new RuntimeException('Database connection failed');
    return $pdo;
}

function sys_setting(string $key, string $default = ''): string {
    return setting(db(), $key, $default);
}

function sys_save_setting(string $key, string $value): void {
    save_setting(db(), $key, $value);
}

function sys_subjects(): array {
    return db()->query('SELECT * FROM subjects ORDER BY display_order ASC, id ASC')->fetchAll() ?: [];
}

function sys_ranked_students(?string $className = null): array {
    $nameExpr = student_name_sql();
    $sql = 'SELECT s.id, COALESCE(s.register_no, s.student_uid, s.register_number) AS register_no, ' . $nameExpr . ' AS name, s.class_name, COALESCE(SUM(m.mark),0) AS total
            FROM students s LEFT JOIN marks m ON m.student_id = s.id';
    $params = [];
    if ($className !== null) { $sql .= ' WHERE s.class_name=:c'; $params['c'] = $className; }
    $sql .= ' GROUP BY s.id, s.register_no, s.student_uid, s.register_number, s.class_name, ' . $nameExpr . ' ORDER BY total DESC, name ASC';
    $st = db()->prepare($sql); $st->execute($params); $rows = $st->fetchAll() ?: [];
    $rank=0; $prev=null;
    foreach ($rows as $i => &$r) {
        if ($prev === null || (float)$r['total'] < (float)$prev) $rank = $i + 1;
        $r['rank_position'] = $rank;
        $prev = (float)$r['total'];
    }
    return $rows;
}

function sys_rank_for_student(int $sid, ?string $className): ?int {
    foreach (sys_ranked_students($className) as $r) if ((int)$r['id'] === $sid) return (int)$r['rank_position'];
    return null;
}

function sys_fetch_student_result(int $id): ?array {
    $db = db();
    $st = $db->prepare('SELECT id, COALESCE(register_no, student_uid, register_number) AS register_no, ' . student_name_sql() . ' AS name, class_name FROM students WHERE id=:id LIMIT 1');
    $st->execute(['id' => $id]);
    $student = $st->fetch();
    if (!$student) return null;

    $markSt = $db->prepare('SELECT subject_id, mark FROM marks WHERE student_id=:id');
    $markSt->execute(['id' => $id]);
    $markMap=[]; foreach($markSt->fetchAll() ?: [] as $m) $markMap[(int)$m['subject_id']] = (float)$m['mark'];

    $subjects=[]; $total=0.0; $max=0.0;
    foreach (sys_subjects() as $sub) {
        $sid=(int)$sub['id'];
        $mark = $markMap[$sid] ?? 0.0;
        $mx = (float)($sub['max_mark'] ?? 50);
        $subjects[] = ['name'=>(string)$sub['subject_name'],'mark'=>$mark,'max'=>$mx,'pass'=>$mark >= (float)$sub['pass_mark']];
        $total += $mark; $max += $mx;
    }
    $pct = $max > 0 ? round(($total/$max)*100,2) : 0.0;
    return [
        'student'=>$student,
        'subjects'=>$subjects,
        'total'=>round($total,2),
        'percentage'=>$pct,
        'grade'=>$pct>=80?'A+':($pct>=70?'A':($pct>=60?'B':($pct>=50?'C':'F'))),
        'promotion'=>$pct>=50?'Promoted':'Not eligible for promotion',
        'status'=>$pct>=50?'PASS':'FAIL',
        'class_rank'=>sys_rank_for_student((int)$student['id'], (string)$student['class_name']),
        'overall_rank'=>sys_rank_for_student((int)$student['id'], null),
    ];
}

function sys_sync_from_google_sheet(string $url): int {
    return sync_from_sheet(db(), $url);
}

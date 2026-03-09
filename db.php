<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function connect_db(array $cfg): PDO {
    $db = db_connect($cfg);
    if (!$db) {
        throw new RuntimeException('Database connection failed.');
    }
    return $db;
}

function ensure_core_tables(PDO $db): void {
    ensure_import_tables($db);
}

function fetch_csv_rows_from_file(string $filePath): array {
    $rows = [];
    if (!is_file($filePath)) return $rows;
    $h = fopen($filePath, 'r');
    if ($h === false) return $rows;

    $header = fgetcsv($h, 0, ',', '"', '\\');
    if (!$header) { fclose($h); return $rows; }
    $header = array_map(fn($v) => strtolower(trim((string)$v)), $header);

    while (($line = fgetcsv($h, 0, ',', '"', '\\')) !== false) {
        if (!array_filter($line, fn($v) => trim((string)$v) !== '')) continue;
        $rows[] = array_combine($header, array_pad($line, count($header), ''));
    }
    fclose($h);
    return $rows;
}

function ensure_exam(PDO $db, int $examId): void {
    $find = $db->prepare('SELECT id FROM exams WHERE id = :id LIMIT 1');
    $find->execute(['id' => $examId]);
    if ($find->fetch()) return;

    $sql = db_driver($db) === 'sqlite'
        ? 'INSERT INTO exams (id, madrasa_id, exam_name, exam_type, exam_date) VALUES (:id, 1, :name, :type, DATE("now"))'
        : 'INSERT INTO exams (id, madrasa_id, exam_name, exam_type, exam_date) VALUES (:id, 1, :name, :type, CURRENT_DATE)';
    $insert = $db->prepare($sql);
    $insert->execute(['id' => $examId, 'name' => 'Imported Exam', 'type' => 'Midterm']);
}

function ensure_subject(PDO $db, int $subjectId): void {
    $driver = db_driver($db);
    if ($driver === 'sqlite') {
        $insert = $db->prepare('INSERT OR IGNORE INTO subjects (id, code, subject_name, max_mark, pass_mark, display_order) VALUES (:id, :code, :name, 50, 18, :ord)');
    } else {
        $insert = $db->prepare('INSERT INTO subjects (id, code, subject_name, max_mark, pass_mark, display_order) VALUES (:id, :code, :name, 50, 18, :ord) ON DUPLICATE KEY UPDATE subject_name=VALUES(subject_name)');
    }
    $insert->execute(['id' => $subjectId, 'code' => 'SUB-' . $subjectId, 'name' => 'Subject ' . $subjectId, 'ord' => $subjectId]);
}

function find_student_by_register(PDO $db, string $register): ?array {
    $st = $db->prepare('SELECT id, COALESCE(register_no, student_uid) AS register_no, full_name, class_name FROM students WHERE register_no = :register_no LIMIT 1');
    $st->execute(['register_no' => $register]);
    return $st->fetch() ?: null;
}

function insert_mark(PDO $db, int $examId, int $studentId, int $subjectId, float $mark): void {
    $driver = db_driver($db);
    $sql = $driver === 'sqlite'
        ? 'INSERT INTO marks (exam_id, student_id, subject_id, mark) VALUES (:exam_id, :student_id, :subject_id, :mark) ON CONFLICT(exam_id, student_id, subject_id) DO UPDATE SET mark=:mark'
        : 'INSERT INTO marks (exam_id, student_id, subject_id, mark) VALUES (:exam_id, :student_id, :subject_id, :mark) ON DUPLICATE KEY UPDATE mark = VALUES(mark)';
    $st = $db->prepare($sql);
    $st->execute(['exam_id' => $examId, 'student_id' => $studentId, 'subject_id' => $subjectId, 'mark' => $mark]);
}

function import_exam_marks_from_csv(PDO $db, string $filePath): array {
    $rows = fetch_csv_rows_from_file($filePath);
    $imported = 0; $skipped = 0; $errors = [];
    $ignore = ['exam_id','exam','register_no','register_number','name','full_name','student_name','class','class_name','gender','subject_id','mark','total','rank'];

    $db->beginTransaction();
    try {
        foreach ($rows as $idx => $raw) {
            $row = normalize_row($raw);
            $line = $idx + 2;
            $examId = (int)($row['exam_id'] ?? 1);
            $register = trim((string)($row['register_no'] ?? ''));
            $name = trim((string)($row['name'] ?? ''));
            $class = trim((string)($row['class'] ?? 'General'));
            $gender = strtolower((string)($row['gender'] ?? 'boy')) === 'girl' ? 'Girl' : 'Boy';

            if ($register === '' || $name === '') { $skipped++; $errors[] = "Line {$line}: missing student info."; continue; }

            $student = find_student_by_register($db, $register);
            if (!$student) {
                $st = db_driver($db)==='sqlite'
                    ? $db->prepare('INSERT INTO students (register_no, full_name, class_name, madrasa_id, gender, attendance_percent) VALUES (:r,:n,:c,1,:g,0) ON CONFLICT(register_no) DO UPDATE SET full_name=:n, class_name=:c')
                    : $db->prepare('INSERT INTO students (register_no, full_name, class_name, madrasa_id, gender, attendance_percent) VALUES (:r,:n,:c,1,:g,0) ON DUPLICATE KEY UPDATE full_name=:n, class_name=:c');
                $st->execute(['r'=>$register,'n'=>$name,'c'=>$class,'g'=>$gender]);
                $student = find_student_by_register($db, $register);
            }
            if (!$student) { $skipped++; $errors[] = "Line {$line}: student unresolved."; continue; }

            ensure_exam($db, $examId);
            $rowImported = 0;

            foreach ($row as $col => $val) {
                if (in_array($col, $ignore, true)) continue;
                if ($val === '' || !is_numeric((string)$val)) continue;
                $sid = abs(crc32($col)) % 100000; if ($sid === 0) $sid = 1;
                ensure_subject($db, $sid);
                insert_mark($db, $examId, (int)$student['id'], $sid, (float)$val);
                $imported++; $rowImported++;
            }

            if ($rowImported === 0 && isset($row['subject_id'], $row['mark']) && is_numeric((string)$row['subject_id']) && is_numeric((string)$row['mark'])) {
                $sid = (int)$row['subject_id'];
                ensure_subject($db, $sid);
                insert_mark($db, $examId, (int)$student['id'], $sid, (float)$row['mark']);
                $imported++;
            } elseif ($rowImported === 0) {
                $skipped++;
            }
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        $errors[] = 'Import failed: ' . $e->getMessage();
    }

    return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
}


function calculate_student_totals(PDO $db, int $examId): array {
    $st = $db->prepare('SELECT s.id AS student_id, s.full_name, s.class_name, SUM(m.mark) AS total_marks, AVG(m.mark) AS average_marks
        FROM marks m INNER JOIN students s ON s.id = m.student_id
        WHERE m.exam_id = :exam_id GROUP BY s.id, s.full_name, s.class_name ORDER BY total_marks DESC');
    $st->execute(['exam_id' => $examId]);
    return $st->fetchAll() ?: [];
}

function generate_rankings(array $totals): array {
    $ranked = []; $rank = 0; $i = 0; $prev = null;
    foreach ($totals as $row) {
        $i++; $score = (float)$row['total_marks'];
        if ($prev === null || $score < $prev) $rank = $i;
        $row['rank'] = $rank; $ranked[] = $row; $prev = $score;
    }
    return $ranked;
}

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

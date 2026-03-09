<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$db = db_connect($config['db']);
if (!$db) {
    die('Database connection failed.');
}

$driver = db_driver($db);
if ($driver === 'sqlite') {
    $db->exec("CREATE TABLE IF NOT EXISTS exams (id INTEGER PRIMARY KEY, madrasa_id INTEGER, exam_name TEXT, exam_type TEXT, exam_date TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS students (id INTEGER PRIMARY KEY AUTOINCREMENT, register_no TEXT UNIQUE, full_name TEXT, class_name TEXT, madrasa_id INTEGER, gender TEXT, attendance_percent REAL)");
    $db->exec("CREATE TABLE IF NOT EXISTS subjects (id INTEGER PRIMARY KEY, code TEXT, subject_name TEXT, max_mark INTEGER, pass_mark INTEGER, display_order INTEGER)");
    $db->exec("CREATE TABLE IF NOT EXISTS marks (exam_id INTEGER, student_id INTEGER, subject_id INTEGER, mark REAL, PRIMARY KEY (exam_id, student_id, subject_id))");
} else {
    $db->exec("CREATE TABLE IF NOT EXISTS exams (id INT PRIMARY KEY, madrasa_id INT, exam_name VARCHAR(191), exam_type VARCHAR(80), exam_date DATE)");
    $db->exec("CREATE TABLE IF NOT EXISTS students (id INT AUTO_INCREMENT PRIMARY KEY, register_no VARCHAR(120) UNIQUE, full_name VARCHAR(191), class_name VARCHAR(80), madrasa_id INT, gender VARCHAR(20), attendance_percent DECIMAL(5,2))");
    $db->exec("CREATE TABLE IF NOT EXISTS subjects (id INT PRIMARY KEY, code VARCHAR(120), subject_name VARCHAR(180), max_mark INT, pass_mark INT, display_order INT)");
    $db->exec("CREATE TABLE IF NOT EXISTS marks (exam_id INT, student_id INT, subject_id INT, mark DECIMAL(8,2), PRIMARY KEY (exam_id, student_id, subject_id))");
}

$message = '';
$previewRows = [];
$sheetUrl = '';
$errors = [];

function sheet_to_csv_url(string $url): string
{
    return normalize_sheet_url($url);
}

function fetch_csv_from_url(string $url): array
{
    return fetch_csv_rows(sheet_to_csv_url($url), 25);
}


function normalize_import_row(array $row): array
{
    $normalized = [];
    foreach ($row as $key => $value) {
        $k = strtolower(trim(str_replace([' ', '-'], '_', (string)$key)));
        $normalized[$k] = is_string($value) ? trim($value) : $value;
    }

    if (isset($normalized['register_number']) && !isset($normalized['register_no'])) $normalized['register_no'] = (string)$normalized['register_number'];
    if (isset($normalized['student_register']) && !isset($normalized['register_no'])) $normalized['register_no'] = (string)$normalized['student_register'];
    if (isset($normalized['full_name']) && !isset($normalized['name'])) $normalized['name'] = (string)$normalized['full_name'];
    if (isset($normalized['student_name']) && !isset($normalized['name'])) $normalized['name'] = (string)$normalized['student_name'];
    if (isset($normalized['class_name']) && !isset($normalized['class'])) $normalized['class'] = (string)$normalized['class_name'];
    if (!isset($normalized['exam_id']) && isset($normalized['exam'])) $normalized['exam_id'] = $normalized['exam'];

    return $normalized;
}

function read_csv_rows(string $file): array
{
    $rows = [];
    if (($handle = fopen($file, 'r')) === false) return $rows;

    $header = fgetcsv($handle, 0, ',', '"', '\\');
    if (!$header) { fclose($handle); return $rows; }

    $header = array_map(fn($h) => strtolower(trim(str_replace([' ', '-'], '_', (string)$h))), $header);

    while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        if (!array_filter($line, fn($v) => trim((string)$v) !== '')) continue;
        if (count($line) !== count($header)) $line = array_pad($line, count($header), '');
        $rows[] = normalize_import_row(array_combine($header, $line) ?: []);
    }

    fclose($handle);
    return $rows;
}

function sync_rows(PDO $db, array $rows, array &$errors = []): int
{
    if (!$rows) return 0;

    $driver = (string)$db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $stmtMarks = $db->prepare('INSERT INTO marks (exam_id, student_id, subject_id, mark) VALUES (:exam_id, :student_id, :subject_id, :mark) ON CONFLICT(exam_id, student_id, subject_id) DO UPDATE SET mark=:mark');
        $stmtStudent = $db->prepare('INSERT INTO students (register_no, full_name, class_name, madrasa_id, gender, attendance_percent) VALUES (:r, :n, :c, 1, :g, 0) ON CONFLICT(register_no) DO UPDATE SET full_name=:n, class_name=:c');
        $stmtEnsureSubject = $db->prepare('INSERT OR IGNORE INTO subjects(id, code, subject_name, max_mark, pass_mark, display_order) VALUES (?, ?, ?, 50, 18, ?)');
        $stmtInsertExam = $db->prepare("INSERT INTO exams (id, madrasa_id, exam_name, exam_type, exam_date) VALUES (?, 1, 'Imported Exam', 'Midterm', DATE('now'))");
    } else {
        $stmtMarks = $db->prepare('INSERT INTO marks (exam_id, student_id, subject_id, mark) VALUES (:exam_id, :student_id, :subject_id, :mark) ON DUPLICATE KEY UPDATE mark=:mark');
        $stmtStudent = $db->prepare('INSERT INTO students (register_no, full_name, class_name, madrasa_id, gender, attendance_percent) VALUES (:r, :n, :c, 1, :g, 0) ON DUPLICATE KEY UPDATE full_name=:n, class_name=:c');
        $stmtEnsureSubject = $db->prepare('INSERT INTO subjects(id, code, subject_name, max_mark, pass_mark, display_order) VALUES (?, ?, ?, 50, 18, ?) ON DUPLICATE KEY UPDATE subject_name=VALUES(subject_name), code=VALUES(code)');
        $stmtInsertExam = $db->prepare("INSERT INTO exams (id, madrasa_id, exam_name, exam_type, exam_date) VALUES (?, 1, 'Imported Exam', 'Midterm', CURRENT_DATE)");
    }

    $stmtGetStudent = $db->prepare('SELECT id FROM students WHERE register_no=:r LIMIT 1');
    $stmtExam = $db->prepare('SELECT id FROM exams WHERE id=?');

    $count = 0;
    $ignoreColumns = ['exam_id','exam','register_no','register_number','student_register','name','full_name','student_name','class','class_name','gender','total','rank','average'];

    $db->beginTransaction();
    try {
        foreach ($rows as $i => $rawRow) {
            $row = normalize_import_row($rawRow);
            $rowIndex = $i + 2;

            $examId = (int)($row['exam_id'] ?? 1);
            $registerNo = trim((string)($row['register_no'] ?? ''));
            $name = trim((string)($row['name'] ?? ''));
            $class = trim((string)($row['class'] ?? 'General'));
            $gender = isset($row['gender']) && strtolower((string)$row['gender']) === 'girl' ? 'Girl' : 'Boy';

            if ($registerNo === '' || $name === '') {
                $errors[] = "Line $rowIndex: Missing student info.";
                continue;
            }

            $stmtStudent->execute(['r' => $registerNo, 'n' => $name, 'c' => $class, 'g' => $gender]);

            $stmtGetStudent->execute(['r' => $registerNo]);
            $studentId = (int)$stmtGetStudent->fetchColumn();
            if ($studentId <= 0) {
                $errors[] = "Line $rowIndex: Failed to get student_id for $registerNo";
                continue;
            }

            $stmtExam->execute([$examId]);
            if (!$stmtExam->fetch()) {
                $stmtInsertExam->execute([$examId]);
            }

            $rowImported = 0;
            foreach ($row as $col => $value) {
                if (in_array($col, $ignoreColumns, true)) continue;
                if ($value === '' || !is_numeric((string)$value)) continue;

                $subjectId = abs(crc32($col)) % 100000;
                if ($subjectId === 0) $subjectId = 1;
                $subjectName = ucwords(str_replace('_', ' ', $col));

                $stmtEnsureSubject->execute([$subjectId, $col, $subjectName, $subjectId]);
                $stmtMarks->execute([':exam_id' => $examId, ':student_id' => $studentId, ':subject_id' => $subjectId, ':mark' => (float)$value]);
                $count++;
                $rowImported++;
            }

            if ($rowImported === 0 && isset($row['subject_id'], $row['mark']) && is_numeric((string)$row['subject_id']) && is_numeric((string)$row['mark'])) {
                $subjectId = (int)$row['subject_id'];
                $stmtEnsureSubject->execute([$subjectId, 'subject_' . $subjectId, 'Subject ' . $subjectId, $subjectId]);
                $stmtMarks->execute([':exam_id' => $examId, ':student_id' => $studentId, ':subject_id' => $subjectId, ':mark' => (float)$row['mark']]);
                $count++;
            }
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        $errors[] = 'Database Error: ' . $e->getMessage();
    }

    return $count;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['sheet_url'])) {
        $sheetUrl = trim((string)$_POST['sheet_url']);
        $previewRows = array_map('normalize_import_row', fetch_csv_from_url($sheetUrl));

        if (isset($_POST['preview'])) {
            $message = !$previewRows
                ? '<p class="msg-bad">Could not fetch data. Check Sheet sharing settings.</p>'
                : "<p class='msg-ok'>Preview fetched " . count($previewRows) . " rows.</p>";
        }

        if (isset($_POST['import'])) {
            $count = sync_rows($db, $previewRows, $errors);
            $message = "<p class='msg-ok'>Successfully synced $count marks rows.</p>";
            if ($errors) $message .= "<p class='msg-bad'>Errors:<br>" . implode('<br>', array_map('e', $errors)) . '</p>';
        }
    }

    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        $previewRows = read_csv_rows($file);

        if (isset($_POST['preview'])) {
            $message = "<p class='msg-ok'>Preview fetched " . count($previewRows) . " rows from CSV.</p>";
        }

        if (isset($_POST['import'])) {
            $count = sync_rows($db, $previewRows, $errors);
            $message = "<p class='msg-ok'>Successfully synced $count marks rows.</p>";
            if ($errors) $message .= "<p class='msg-bad'>Errors:<br>" . implode('<br>', array_map('e', $errors)) . '</p>';
        }
    }
}

render_header('Admin Bulk Upload - Sheet / CSV');
?>

<div class="card">
    <h2>Google Sheet or CSV Import</h2>

    <?= $message ?>

    <form method="post" enctype="multipart/form-data">
        <input type="text" name="sheet_url" placeholder="Paste Google Sheet URL" value="<?= e($sheetUrl) ?>">
        <p style="text-align:center;margin:10px 0;">OR</p>
        <input type="file" name="csv_file" accept=".csv">

        <div style="margin-top:10px;display:flex;gap:10px;">
            <button type="submit" name="preview">Preview</button>
            <button type="submit" name="import">Import</button>
        </div>
    </form>
</div>

<?php if ($previewRows): ?>
<div class="card">
    <h3>Preview (First 10 Rows)</h3>
    <table>
        <tr>
            <?php foreach (array_keys($previewRows[0]) as $head): ?>
                <th><?= e((string)$head) ?></th>
            <?php endforeach; ?>
        </tr>
        <?php foreach (array_slice($previewRows, 0, 10) as $row): ?>
            <tr>
                <?php foreach ($row as $val): ?>
                    <td><?= e((string)$val) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>

<?php render_footer();

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

function sync_rows(PDO $db, array $rows, array &$errors = []): int
{
    if (!$rows) return 0;

    $subjects = [
        'quran' => 7, 'hiflu' => 8, 'fiqh' => 17, 'aqeeda' => 19, 'thajveed' => 14, 'droos' => 22,
        'lisanul_quran' => 10, 'thareekh' => 18, 'thadreebu' => 9, 'thafheem_reading' => 1,
        'thafheem_write' => 2, 'duroos_read' => 3, 'duroos_write' => 4, 'dheeniyath_akhlaq' => 5,
        'kedayuth' => 6, 'thafseer' => 21
    ];

    $driver = (string)$db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $stmtMarks = $db->prepare('INSERT INTO marks (exam_id, student_id, subject_id, mark) VALUES (:exam_id, :student_id, :subject_id, :mark) ON CONFLICT(exam_id, student_id, subject_id) DO UPDATE SET mark=:mark');
        $stmtStudent = $db->prepare('INSERT INTO students (register_no, full_name, class_name, madrasa_id, gender, attendance_percent) VALUES (:r, :n, :c, 1, :g, 0) ON CONFLICT(register_no) DO UPDATE SET full_name=:n, class_name=:c');
    } else {
        $stmtMarks = $db->prepare('INSERT INTO marks (exam_id, student_id, subject_id, mark) VALUES (:exam_id, :student_id, :subject_id, :mark) ON DUPLICATE KEY UPDATE mark=:mark');
        $stmtStudent = $db->prepare('INSERT INTO students (register_no, full_name, class_name, madrasa_id, gender, attendance_percent) VALUES (:r, :n, :c, 1, :g, 0) ON DUPLICATE KEY UPDATE full_name=:n, class_name=:c');
    }

    $stmtGetStudent = $db->prepare('SELECT id FROM students WHERE register_no=:r LIMIT 1');
    $stmtExam = $db->prepare('SELECT id FROM exams WHERE id=?');
    $stmtInsertExam = $db->prepare("INSERT INTO exams (id, madrasa_id, exam_name, exam_type, exam_date) VALUES (?, 1, 'Imported Exam', 'Midterm', DATE('now'))");
    $stmtEnsureSubject = $db->prepare("INSERT OR IGNORE INTO subjects(id, code, subject_name, max_mark, pass_mark, display_order) VALUES (?, ?, ?, 50, 18, ?)");

    $count = 0;

    $db->beginTransaction();
    try {
        foreach ($rows as $i => $row) {
            $rowIndex = $i + 2;

            if (!isset($row['exam_id'], $row['register_no'], $row['name'], $row['class'])) {
                $errors[] = "Line $rowIndex: Missing required fields.";
                continue;
            }

            $examId = (int)$row['exam_id'];
            $registerNo = trim((string)$row['register_no']);
            $name = trim((string)$row['name']);
            $class = trim((string)$row['class']);
            $gender = isset($row['gender']) && strtolower((string)$row['gender']) === 'girl' ? 'Girl' : 'Boy';

            if ($registerNo === '' || $name === '' || $class === '') {
                $errors[] = "Line $rowIndex: Empty register_no, name, or class.";
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
                try { $stmtInsertExam->execute([$examId]); } catch (Throwable $e) {
                    $errors[] = "Line $rowIndex: Failed to create exam $examId";
                    continue;
                }
            }

            foreach ($subjects as $subName => $subId) {
                if (!isset($row[$subName]) || $row[$subName] === '') continue;

                $mark = (float)$row[$subName];
                if (!is_numeric((string)$row[$subName])) continue;

                $stmtEnsureSubject->execute([$subId, $subName, ucwords(str_replace('_', ' ', $subName)), $subId]);

                $stmtMarks->execute([
                    ':exam_id' => $examId,
                    ':student_id' => $studentId,
                    ':subject_id' => $subId,
                    ':mark' => $mark
                ]);

                $count++;
            }
        }

        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        $errors[] = 'Database Error: ' . $e->getMessage();
    }

    return $count;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['sheet_url'])) {
        $sheetUrl = trim((string)$_POST['sheet_url']);
        $previewRows = fetch_csv_from_url($sheetUrl);

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
        $previewRows = [];

        if (($handle = fopen($file, 'r')) !== false) {
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            $cleanHeader = array_map(fn($h) => str_replace(' ', '_', strtolower(trim((string)$h))), $header ?: []);

            while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                if (count($line) !== count($cleanHeader)) continue;
                $previewRows[] = array_combine($cleanHeader, $line);
            }

            fclose($handle);
        }

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

<?php
session_start();
header('Content-Type: application/json');

const DB_FILE = __DIR__ . '/data/madrasa.sqlite';
const UPLOAD_DIR = __DIR__ . '/uploads';

function out(array $data, int $status = 200): void {
  http_response_code($status);
  echo json_encode($data);
  exit;
}

function pdo(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;
  if (!is_dir(dirname(DB_FILE))) mkdir(dirname(DB_FILE), 0777, true);
  $pdo = new PDO('sqlite:' . DB_FILE);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  initSchema($pdo);
  return $pdo;
}

function initSchema(PDO $db): void {
  $db->exec("CREATE TABLE IF NOT EXISTS madrasas (id INTEGER PRIMARY KEY, name TEXT UNIQUE, location TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER NULL, username TEXT UNIQUE, password_hash TEXT, role TEXT, full_name TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS students (id INTEGER PRIMARY KEY AUTOINCREMENT, student_uid TEXT UNIQUE, madrasa_id INTEGER, full_name TEXT, class_name TEXT, gender TEXT, photo_path TEXT, attendance_percent REAL DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS teachers (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, full_name TEXT, subject_name TEXT, class_name TEXT, attendance_percent REAL DEFAULT 100, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS exams (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, exam_name TEXT, exam_type TEXT, exam_date TEXT, created_by INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS results (id INTEGER PRIMARY KEY AUTOINCREMENT, exam_id INTEGER, student_id INTEGER, marks_math INTEGER DEFAULT 0, marks_science INTEGER DEFAULT 0, marks_english INTEGER DEFAULT 0, total_marks INTEGER DEFAULT 0, grade TEXT DEFAULT 'F', rank_position INTEGER)");
  $db->exec("CREATE TABLE IF NOT EXISTS mark_edit_history (id INTEGER PRIMARY KEY AUTOINCREMENT, result_id INTEGER, edited_by INTEGER, old_total INTEGER, new_total INTEGER, edit_note TEXT, edited_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS announcements (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, title TEXT, body TEXT, is_important INTEGER DEFAULT 0, expiry_date TEXT, created_by INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS attendance_records (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, student_id INTEGER, attendance_date TEXT, status TEXT)");
  $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uk_student_day ON attendance_records(student_id, attendance_date)");

  $count = (int)$db->query("SELECT COUNT(*) FROM madrasas")->fetchColumn();
  if ($count === 0) {
    $names = ['Noorul Huda Madrasa','Darul Uloom Central','Falah Islamic Academy','Rahmaniya Madrasa','Sirajul Islam Madrasa','Hidayathul Quran Center','Anwarul Islam Madrasa','Nadwath Students Campus','Ameenul Uloom Madrasa','Thajul Huda School','Badria Dars','Misbahul Hudha','Najathul Islam Madrasa'];
    $stmt = $db->prepare("INSERT INTO madrasas(id,name,location) VALUES(?,?,?)");
    foreach ($names as $i => $name) $stmt->execute([$i+1, $name, 'Kerala']);
  }

  $uCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
  if ($uCount === 0) {
    $ins = $db->prepare("INSERT INTO users(madrasa_id,username,password_hash,role,full_name) VALUES(?,?,?,?,?)");
    $ins->execute([null, 'admin', password_hash('madrasa123', PASSWORD_DEFAULT), 'Admin', 'System Admin']);
    $ins->execute([1, 'teacher', password_hash('teacher123', PASSWORD_DEFAULT), 'Teacher', 'Main Teacher']);
    $ins->execute([null, 'viewer', password_hash('viewer123', PASSWORD_DEFAULT), 'Viewer', 'Viewer']);
  }
}

function body(): array {
  return json_decode(file_get_contents('php://input'), true) ?? [];
}

function user(): ?array { return $_SESSION['user'] ?? null; }
function need(array $roles): array {
  $u = user();
  if (!$u || !in_array($u['role'], $roles, true)) out(['error'=>'Unauthorized'], 403);
  return $u;
}

function grade(float $avg): string {
  if ($avg >= 90) return 'A+';
  if ($avg >= 80) return 'A';
  if ($avg >= 70) return 'B';
  if ($avg >= 60) return 'C';
  if ($avg >= 50) return 'D';
  return 'F';
}

function recalcRanks(PDO $db, int $examId): void {
  $rows = $db->prepare("SELECT id FROM results WHERE exam_id = ? ORDER BY total_marks DESC, id ASC");
  $rows->execute([$examId]);
  $upd = $db->prepare("UPDATE results SET rank_position = ? WHERE id = ?");
  $rank = 1;
  foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $upd->execute([$rank++, $r['id']]);
  }
}

$action = $_GET['action'] ?? 'bootstrap';
$db = pdo();

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $b = body();
  $q = $db->prepare('SELECT id, username, role, madrasa_id, password_hash FROM users WHERE username = ? LIMIT 1');
  $q->execute([strtolower(trim($b['username'] ?? ''))]);
  $u = $q->fetch(PDO::FETCH_ASSOC);
  if (!$u || !password_verify((string)($b['password'] ?? ''), $u['password_hash']) || $u['role'] !== ($b['role'] ?? 'Viewer')) out(['error'=>'Invalid credentials'], 401);
  $_SESSION['user'] = ['id'=>(int)$u['id'], 'username'=>$u['username'], 'role'=>$u['role'], 'madrasa_id'=>$u['madrasa_id'] ? (int)$u['madrasa_id'] : null];
  out(['ok'=>true,'user'=>$_SESSION['user']]);
}

if ($action === 'logout') {
  session_destroy();
  out(['ok'=>true]);
}

if ($action === 'upload_photo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin','Teacher']);
  if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
  if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) out(['error'=>'Photo upload failed'], 400);
  $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) out(['error'=>'Invalid image format'], 400);
  $name = 'student_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = UPLOAD_DIR . '/' . $name;
  move_uploaded_file($_FILES['photo']['tmp_name'], $dest);
  out(['ok'=>true,'path'=>'uploads/' . $name]);
}

if ($action === 'bootstrap') {
  $mid = (int)($_GET['madrasa_id'] ?? 1);
  $madrasas = $db->query('SELECT id,name,location FROM madrasas ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
  $students = $db->prepare('SELECT * FROM students WHERE madrasa_id = ? ORDER BY id DESC'); $students->execute([$mid]);
  $teachers = $db->prepare('SELECT * FROM teachers WHERE madrasa_id = ? ORDER BY id DESC'); $teachers->execute([$mid]);
  $ann = $db->prepare('SELECT * FROM announcements WHERE madrasa_id = ? ORDER BY id DESC'); $ann->execute([$mid]);
  $attendance = $db->prepare('SELECT ar.*, s.student_uid FROM attendance_records ar JOIN students s ON s.id = ar.student_id WHERE ar.madrasa_id = ? ORDER BY ar.attendance_date DESC'); $attendance->execute([$mid]);
  $results = $db->prepare('SELECT r.*, e.exam_name, e.exam_type, s.student_uid FROM results r JOIN exams e ON e.id=r.exam_id JOIN students s ON s.id=r.student_id WHERE e.madrasa_id = ? ORDER BY r.id DESC'); $results->execute([$mid]);
  $history = $db->prepare('SELECT * FROM mark_edit_history ORDER BY edited_at DESC LIMIT 200'); $history->execute();
  out(['user'=>user(),'madrasas'=>$madrasas,'students'=>$students->fetchAll(PDO::FETCH_ASSOC),'teachers'=>$teachers->fetchAll(PDO::FETCH_ASSOC),'announcements'=>$ann->fetchAll(PDO::FETCH_ASSOC),'attendance'=>$attendance->fetchAll(PDO::FETCH_ASSOC),'results'=>$results->fetchAll(PDO::FETCH_ASSOC),'markHistory'=>$history->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'student_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin','Teacher']);
  $b = body();
  if (!empty($b['id'])) {
    $q = $db->prepare('UPDATE students SET full_name=?, class_name=?, gender=?, attendance_percent=?, photo_path=? WHERE id=?');
    $q->execute([$b['full_name'],$b['class_name'],$b['gender'],$b['attendance_percent'],$b['photo_path'] ?? null,$b['id']]);
  } else {
    $uid = 'STD-' . date('ymd') . '-' . random_int(1000, 9999);
    $q = $db->prepare('INSERT INTO students(student_uid,madrasa_id,full_name,class_name,gender,photo_path,attendance_percent) VALUES(?,?,?,?,?,?,?)');
    $q->execute([$uid,$b['madrasa_id'],$b['full_name'],$b['class_name'],$b['gender'],$b['photo_path'] ?? null,$b['attendance_percent'] ?? 0]);
  }
  out(['ok'=>true]);
}

if ($action === 'student_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin']);
  $db->prepare('DELETE FROM students WHERE id = ?')->execute([(int)(body()['id'] ?? 0)]);
  out(['ok'=>true]);
}

if ($action === 'teacher_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin']);
  $b = body();
  $db->prepare('INSERT INTO teachers(madrasa_id,full_name,subject_name,class_name,attendance_percent) VALUES(?,?,?,?,?)')->execute([$b['madrasa_id'],$b['full_name'],$b['subject_name'],$b['class_name'],$b['attendance_percent'] ?? 100]);
  out(['ok'=>true]);
}

if ($action === 'announcement_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = need(['Admin']);
  $b = body();
  $db->prepare('INSERT INTO announcements(madrasa_id,title,body,is_important,expiry_date,created_by) VALUES(?,?,?,?,?,?)')->execute([$b['madrasa_id'],$b['title'],$b['body'],!empty($b['is_important'])?1:0,$b['expiry_date'],$u['id']]);
  out(['ok'=>true]);
}

if ($action === 'attendance_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin','Teacher']);
  $b = body();
  $q = $db->prepare('INSERT INTO attendance_records(madrasa_id,student_id,attendance_date,status) VALUES(?,?,?,?) ON CONFLICT(student_id,attendance_date) DO UPDATE SET status=excluded.status');
  foreach (($b['rows'] ?? []) as $r) $q->execute([$b['madrasa_id'],$r['student_id'],$b['attendance_date'],$r['status']]);
  out(['ok'=>true]);
}

if ($action === 'result_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = need(['Admin','Teacher']);
  $b = body();
  $examId = (int)($b['exam_id'] ?? 0);
  if ($examId === 0) {
    $db->prepare('INSERT INTO exams(madrasa_id,exam_name,exam_type,exam_date,created_by) VALUES(?,?,?,?,?)')->execute([$b['madrasa_id'],$b['exam_name'],$b['exam_type'],$b['exam_date'] ?? null,$u['id']]);
    $examId = (int)$db->lastInsertId();
  }
  $total = (int)$b['marks_math'] + (int)$b['marks_science'] + (int)$b['marks_english'];
  $gr = grade($total / 3);
  if (!empty($b['id'])) {
    $old = $db->prepare('SELECT total_marks FROM results WHERE id=?'); $old->execute([$b['id']]);
    $oldTotal = (int)$old->fetchColumn();
    $db->prepare('UPDATE results SET marks_math=?, marks_science=?, marks_english=?, total_marks=?, grade=? WHERE id=?')->execute([$b['marks_math'],$b['marks_science'],$b['marks_english'],$total,$gr,$b['id']]);
    $db->prepare('INSERT INTO mark_edit_history(result_id,edited_by,old_total,new_total,edit_note) VALUES(?,?,?,?,?)')->execute([$b['id'],$u['id'],$oldTotal,$total,$b['edit_note'] ?? 'Marks updated']);
  } else {
    $db->prepare('INSERT INTO results(exam_id,student_id,marks_math,marks_science,marks_english,total_marks,grade) VALUES(?,?,?,?,?,?,?)')->execute([$examId,$b['student_id'],$b['marks_math'],$b['marks_science'],$b['marks_english'],$total,$gr]);
  }
  recalcRanks($db, $examId);
  out(['ok'=>true]);
}

if ($action === 'report_card') {
  $studentId = (int)($_GET['student_id'] ?? 0);
  $q = $db->prepare('SELECT s.full_name, s.student_uid, s.class_name, e.exam_name, e.exam_type, r.marks_math, r.marks_science, r.marks_english, r.total_marks, r.grade, r.rank_position FROM results r JOIN students s ON s.id=r.student_id JOIN exams e ON e.id=r.exam_id WHERE s.id=? ORDER BY r.id DESC');
  $q->execute([$studentId]);
  out(['rows'=>$q->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'backup_json') {
  $payload = [
    'madrasas' => $db->query('SELECT * FROM madrasas')->fetchAll(PDO::FETCH_ASSOC),
    'students' => $db->query('SELECT * FROM students')->fetchAll(PDO::FETCH_ASSOC),
    'teachers' => $db->query('SELECT * FROM teachers')->fetchAll(PDO::FETCH_ASSOC),
    'exams' => $db->query('SELECT * FROM exams')->fetchAll(PDO::FETCH_ASSOC),
    'results' => $db->query('SELECT * FROM results')->fetchAll(PDO::FETCH_ASSOC),
    'announcements' => $db->query('SELECT * FROM announcements')->fetchAll(PDO::FETCH_ASSOC),
    'attendance_records' => $db->query('SELECT * FROM attendance_records')->fetchAll(PDO::FETCH_ASSOC)
  ];
  out($payload);
}

out(['error'=>'Unknown action'], 404);

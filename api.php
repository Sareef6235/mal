<?php
session_start();
header('Content-Type: application/json');

const DB_FILE = __DIR__ . '/data/madrasa.sqlite';
const UPLOAD_DIR = __DIR__ . '/uploads';

function out(array $data, int $status = 200): void { http_response_code($status); echo json_encode($data); exit; }
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }
function user(): ?array { return $_SESSION['user'] ?? null; }
function need(array $roles): array { $u = user(); if (!$u || !in_array($u['role'], $roles, true)) out(['error'=>'Unauthorized'], 403); return $u; }
function grade(float $avg): string { return $avg>=90?'A+':($avg>=80?'A':($avg>=70?'B':($avg>=60?'C':($avg>=50?'D':'F')))); }

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
  $db->exec("CREATE TABLE IF NOT EXISTS madrasas (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE, location TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER NULL, username TEXT UNIQUE, password_hash TEXT, role TEXT, full_name TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS students (id INTEGER PRIMARY KEY AUTOINCREMENT, student_uid TEXT UNIQUE, madrasa_id INTEGER, full_name TEXT, class_name TEXT, gender TEXT, photo_path TEXT, attendance_percent REAL DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS teachers (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, full_name TEXT, subject_name TEXT, class_name TEXT, attendance_percent REAL DEFAULT 100, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS exams (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, exam_name TEXT, exam_type TEXT, exam_date TEXT, created_by INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS results (id INTEGER PRIMARY KEY AUTOINCREMENT, exam_id INTEGER, student_id INTEGER, marks_math INTEGER DEFAULT 0, marks_science INTEGER DEFAULT 0, marks_english INTEGER DEFAULT 0, total_marks INTEGER DEFAULT 0, grade TEXT DEFAULT 'F', rank_position INTEGER)");
  $db->exec("CREATE TABLE IF NOT EXISTS mark_edit_history (id INTEGER PRIMARY KEY AUTOINCREMENT, result_id INTEGER, edited_by INTEGER, old_total INTEGER, new_total INTEGER, edit_note TEXT, edited_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS announcements (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, title TEXT, body TEXT, is_important INTEGER DEFAULT 0, expiry_date TEXT, created_by INTEGER, created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
  $db->exec("CREATE TABLE IF NOT EXISTS attendance_records (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, student_id INTEGER, attendance_date TEXT, status TEXT)");
  $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS uk_student_day ON attendance_records(student_id, attendance_date)");
  $db->exec("CREATE TABLE IF NOT EXISTS messages (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, sender_role TEXT, sender_name TEXT, body TEXT, admin_reply TEXT DEFAULT NULL, status TEXT DEFAULT 'Open', created_at TEXT DEFAULT CURRENT_TIMESTAMP, replied_at TEXT DEFAULT NULL)");
  $db->exec("CREATE TABLE IF NOT EXISTS payrolls (id INTEGER PRIMARY KEY AUTOINCREMENT, madrasa_id INTEGER, teacher_id INTEGER NULL, staff_name TEXT, month_key TEXT, basic_amount REAL DEFAULT 0, allowance REAL DEFAULT 0, deduction REAL DEFAULT 0, net_amount REAL DEFAULT 0, status TEXT DEFAULT 'Unpaid', created_at TEXT DEFAULT CURRENT_TIMESTAMP)");

  $count = (int)$db->query("SELECT COUNT(*) FROM madrasas")->fetchColumn();
  if ($count === 0) {
    $names = ['Noorul Huda Madrasa','Darul Uloom Central','Falah Islamic Academy','Rahmaniya Madrasa','Sirajul Islam Madrasa','Hidayathul Quran Center','Anwarul Islam Madrasa','Nadwath Students Campus','Ameenul Uloom Madrasa','Thajul Huda School','Badria Dars','Misbahul Hudha','Najathul Islam Madrasa'];
    $stmt = $db->prepare("INSERT INTO madrasas(name,location) VALUES(?,?)");
    foreach ($names as $name) $stmt->execute([$name, 'Kerala']);
  }

  $uCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
  if ($uCount === 0) {
    $ins = $db->prepare("INSERT INTO users(madrasa_id,username,password_hash,role,full_name) VALUES(?,?,?,?,?)");
    $ins->execute([null, 'admin', password_hash('madrasa123', PASSWORD_DEFAULT), 'Admin', 'System Admin']);
    $ins->execute([1, 'teacher', password_hash('teacher123', PASSWORD_DEFAULT), 'Teacher', 'Main Teacher']);
    $ins->execute([null, 'viewer', password_hash('viewer123', PASSWORD_DEFAULT), 'Viewer', 'Viewer']);
  }
}

function recalcRanks(PDO $db, int $examId): void {
  $rows = $db->prepare("SELECT id FROM results WHERE exam_id = ? ORDER BY total_marks DESC, id ASC");
  $rows->execute([$examId]);
  $upd = $db->prepare("UPDATE results SET rank_position = ? WHERE id = ?");
  $rank = 1;
  foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) $upd->execute([$rank++, $r['id']]);
}

$action = $_GET['action'] ?? 'bootstrap';
$db = pdo();

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $b = body();
  $q = $db->prepare('SELECT id, username, role, madrasa_id, password_hash FROM users WHERE username = ? LIMIT 1');
  $q->execute([strtolower(trim((string)($b['username'] ?? '')))]);
  $u = $q->fetch(PDO::FETCH_ASSOC);
  if (!$u || !password_verify((string)($b['password'] ?? ''), $u['password_hash']) || $u['role'] !== ($b['role'] ?? 'Viewer')) out(['error'=>'Invalid credentials'], 401);
  $_SESSION['user'] = ['id'=>(int)$u['id'], 'username'=>$u['username'], 'role'=>$u['role'], 'madrasa_id'=>$u['madrasa_id'] ? (int)$u['madrasa_id'] : null];
  out(['ok'=>true,'user'=>$_SESSION['user']]);
}
if ($action === 'logout') { session_destroy(); out(['ok'=>true]); }

if ($action === 'upload_photo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin','Teacher']);
  if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
  if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) out(['error'=>'Photo upload failed'], 400);
  $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) out(['error'=>'Invalid image format'], 400);
  $name = 'student_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . '/' . $name);
  out(['ok'=>true,'path'=>'uploads/' . $name]);
}

if ($action === 'bootstrap') {
  $mid = (int)($_GET['madrasa_id'] ?? 1);
  $madrasas = $db->query('SELECT id,name,location FROM madrasas ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
  $students = $db->prepare('SELECT * FROM students WHERE madrasa_id = ? ORDER BY id DESC'); $students->execute([$mid]);
  $teachers = $db->prepare('SELECT * FROM teachers WHERE madrasa_id = ? ORDER BY id DESC'); $teachers->execute([$mid]);
  $ann = $db->prepare('SELECT * FROM announcements WHERE madrasa_id = ? ORDER BY id DESC'); $ann->execute([$mid]);
  $attendance = $db->prepare('SELECT ar.*, s.student_uid FROM attendance_records ar JOIN students s ON s.id = ar.student_id WHERE ar.madrasa_id = ? ORDER BY ar.attendance_date DESC'); $attendance->execute([$mid]);
  $results = $db->prepare('SELECT r.*, e.exam_name, e.exam_type, s.student_uid, s.full_name FROM results r JOIN exams e ON e.id=r.exam_id JOIN students s ON s.id=r.student_id WHERE e.madrasa_id = ? ORDER BY r.id DESC'); $results->execute([$mid]);
  $history = $db->query('SELECT * FROM mark_edit_history ORDER BY edited_at DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
  $messages = $db->prepare('SELECT * FROM messages WHERE madrasa_id = ? ORDER BY id DESC'); $messages->execute([$mid]);
  $payrolls = $db->prepare('SELECT p.*, t.full_name AS teacher_name FROM payrolls p LEFT JOIN teachers t ON t.id=p.teacher_id WHERE p.madrasa_id = ? ORDER BY p.id DESC'); $payrolls->execute([$mid]);
  out(['user'=>user(),'madrasas'=>$madrasas,'students'=>$students->fetchAll(PDO::FETCH_ASSOC),'teachers'=>$teachers->fetchAll(PDO::FETCH_ASSOC),'announcements'=>$ann->fetchAll(PDO::FETCH_ASSOC),'attendance'=>$attendance->fetchAll(PDO::FETCH_ASSOC),'results'=>$results->fetchAll(PDO::FETCH_ASSOC),'markHistory'=>$history,'messages'=>$messages->fetchAll(PDO::FETCH_ASSOC),'payrolls'=>$payrolls->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'madrasa_create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin']); $b = body();
  $db->prepare('INSERT INTO madrasas(name,location) VALUES(?,?)')->execute([trim((string)$b['name']), trim((string)($b['location'] ?? ''))]);
  out(['ok'=>true]);
}

if ($action === 'student_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin','Teacher']); $b = body();
  if (!empty($b['id'])) {
    $db->prepare('UPDATE students SET full_name=?, class_name=?, gender=?, attendance_percent=?, photo_path=? WHERE id=?')->execute([$b['full_name'],$b['class_name'],$b['gender'],$b['attendance_percent'],$b['photo_path'] ?? null,$b['id']]);
  } else {
    $uid = 'STD-' . date('ymd') . '-' . random_int(1000, 9999);
    $db->prepare('INSERT INTO students(student_uid,madrasa_id,full_name,class_name,gender,photo_path,attendance_percent) VALUES(?,?,?,?,?,?,?)')->execute([$uid,$b['madrasa_id'],$b['full_name'],$b['class_name'],$b['gender'],$b['photo_path'] ?? null,$b['attendance_percent'] ?? 0]);
  }
  out(['ok'=>true]);
}
if ($action === 'student_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') { need(['Admin']); $db->prepare('DELETE FROM students WHERE id=?')->execute([(int)(body()['id'] ?? 0)]); out(['ok'=>true]); }
if ($action === 'teacher_save' && $_SERVER['REQUEST_METHOD'] === 'POST') { need(['Admin']); $b = body(); $db->prepare('INSERT INTO teachers(madrasa_id,full_name,subject_name,class_name,attendance_percent) VALUES(?,?,?,?,?)')->execute([$b['madrasa_id'],$b['full_name'],$b['subject_name'],$b['class_name'],$b['attendance_percent'] ?? 100]); out(['ok'=>true]); }
if ($action === 'announcement_save' && $_SERVER['REQUEST_METHOD'] === 'POST') { $u=need(['Admin']); $b=body(); $db->prepare('INSERT INTO announcements(madrasa_id,title,body,is_important,expiry_date,created_by) VALUES(?,?,?,?,?,?)')->execute([$b['madrasa_id'],$b['title'],$b['body'],!empty($b['is_important'])?1:0,$b['expiry_date'],$u['id']]); out(['ok'=>true]); }

if ($action === 'attendance_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin','Teacher']); $b = body();
  $q = $db->prepare('INSERT INTO attendance_records(madrasa_id,student_id,attendance_date,status) VALUES(?,?,?,?) ON CONFLICT(student_id,attendance_date) DO UPDATE SET status=excluded.status');
  foreach (($b['rows'] ?? []) as $r) $q->execute([$b['madrasa_id'],$r['student_id'],$b['attendance_date'],$r['status']]);
  $db->prepare('UPDATE students SET attendance_percent = (SELECT ROUND((SUM(CASE WHEN status="Present" THEN 1 ELSE 0 END)*100.0)/COUNT(*),2) FROM attendance_records ar WHERE ar.student_id=students.id) WHERE madrasa_id=?')->execute([$b['madrasa_id']]);
  out(['ok'=>true]);
}

if ($action === 'attendance_monthly') {
  $mid = (int)($_GET['madrasa_id'] ?? 1); $month = (string)($_GET['month'] ?? date('Y-m'));
  $q = $db->prepare('SELECT attendance_date, status, COUNT(*) cnt FROM attendance_records WHERE madrasa_id=? AND substr(attendance_date,1,7)=? GROUP BY attendance_date,status ORDER BY attendance_date');
  $q->execute([$mid,$month]); out(['rows'=>$q->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'result_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = need(['Admin','Teacher']); $b = body(); $examId = (int)($b['exam_id'] ?? 0);
  if ($examId === 0) { $db->prepare('INSERT INTO exams(madrasa_id,exam_name,exam_type,exam_date,created_by) VALUES(?,?,?,?,?)')->execute([$b['madrasa_id'],$b['exam_name'],$b['exam_type'],$b['exam_date'] ?? null,$u['id']]); $examId = (int)$db->lastInsertId(); }
  $total = (int)$b['marks_math'] + (int)$b['marks_science'] + (int)$b['marks_english']; $gr = grade($total / 3);
  if (!empty($b['id'])) {
    $old = $db->prepare('SELECT total_marks FROM results WHERE id=?'); $old->execute([$b['id']]); $oldTotal = (int)$old->fetchColumn();
    $db->prepare('UPDATE results SET marks_math=?, marks_science=?, marks_english=?, total_marks=?, grade=? WHERE id=?')->execute([$b['marks_math'],$b['marks_science'],$b['marks_english'],$total,$gr,$b['id']]);
    $db->prepare('INSERT INTO mark_edit_history(result_id,edited_by,old_total,new_total,edit_note) VALUES(?,?,?,?,?)')->execute([$b['id'],$u['id'],$oldTotal,$total,$b['edit_note'] ?? 'Marks updated']);
  } else {
    $db->prepare('INSERT INTO results(exam_id,student_id,marks_math,marks_science,marks_english,total_marks,grade) VALUES(?,?,?,?,?,?,?)')->execute([$examId,$b['student_id'],$b['marks_math'],$b['marks_science'],$b['marks_english'],$total,$gr]);
  }
  recalcRanks($db, $examId); out(['ok'=>true]);
}

if ($action === 'message_send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = need(['Admin','Teacher','Viewer']); $b = body();
  $db->prepare('INSERT INTO messages(madrasa_id,sender_role,sender_name,body,status) VALUES(?,?,?,?,?)')->execute([$b['madrasa_id'],$u['role'],$u['username'],$b['body'],'Open']);
  out(['ok'=>true]);
}
if ($action === 'message_reply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin']); $b = body();
  $db->prepare('UPDATE messages SET admin_reply=?, status="Replied", replied_at=datetime("now") WHERE id=?')->execute([$b['admin_reply'],$b['id']]);
  out(['ok'=>true]);
}

if ($action === 'payroll_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin']); $b = body();
  $net = (float)$b['basic_amount'] + (float)$b['allowance'] - (float)$b['deduction'];
  $db->prepare('INSERT INTO payrolls(madrasa_id,teacher_id,staff_name,month_key,basic_amount,allowance,deduction,net_amount,status) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$b['madrasa_id'],$b['teacher_id'] ?: null,$b['staff_name'],$b['month_key'],$b['basic_amount'],$b['allowance'],$b['deduction'],$net,$b['status'] ?? 'Unpaid']);
  out(['ok'=>true]);
}
if ($action === 'payroll_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') { need(['Admin']); $db->prepare('DELETE FROM payrolls WHERE id=?')->execute([(int)(body()['id'] ?? 0)]); out(['ok'=>true]); }

if ($action === 'report_card') {
  $studentId = (int)($_GET['student_id'] ?? 0);
  $q = $db->prepare('SELECT s.full_name, s.student_uid, s.class_name, e.exam_name, e.exam_type, r.marks_math, r.marks_science, r.marks_english, r.total_marks, r.grade, r.rank_position FROM results r JOIN students s ON s.id=r.student_id JOIN exams e ON e.id=r.exam_id WHERE s.id=? ORDER BY r.id DESC');
  $q->execute([$studentId]); out(['rows'=>$q->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'backup_json') {
  $payload = ['madrasas'=>$db->query('SELECT * FROM madrasas')->fetchAll(PDO::FETCH_ASSOC),'students'=>$db->query('SELECT * FROM students')->fetchAll(PDO::FETCH_ASSOC),'teachers'=>$db->query('SELECT * FROM teachers')->fetchAll(PDO::FETCH_ASSOC),'exams'=>$db->query('SELECT * FROM exams')->fetchAll(PDO::FETCH_ASSOC),'results'=>$db->query('SELECT * FROM results')->fetchAll(PDO::FETCH_ASSOC),'announcements'=>$db->query('SELECT * FROM announcements')->fetchAll(PDO::FETCH_ASSOC),'attendance_records'=>$db->query('SELECT * FROM attendance_records')->fetchAll(PDO::FETCH_ASSOC),'messages'=>$db->query('SELECT * FROM messages')->fetchAll(PDO::FETCH_ASSOC),'payrolls'=>$db->query('SELECT * FROM payrolls')->fetchAll(PDO::FETCH_ASSOC)];
  out($payload);
}

if ($action === 'import_json' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['Admin']); $b = body();
  $tables = ['students','teachers','exams','results','announcements','attendance_records','messages','payrolls'];
  foreach ($tables as $t) { if (!isset($b[$t]) || !is_array($b[$t])) continue; $db->exec("DELETE FROM $t"); }
  foreach (($b['students'] ?? []) as $r) $db->prepare('INSERT INTO students(id,student_uid,madrasa_id,full_name,class_name,gender,photo_path,attendance_percent,created_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$r['id'],$r['student_uid'],$r['madrasa_id'],$r['full_name'],$r['class_name'],$r['gender'],$r['photo_path'],$r['attendance_percent'],$r['created_at']]);
  foreach (($b['teachers'] ?? []) as $r) $db->prepare('INSERT INTO teachers(id,madrasa_id,full_name,subject_name,class_name,attendance_percent,created_at) VALUES(?,?,?,?,?,?,?)')->execute([$r['id'],$r['madrasa_id'],$r['full_name'],$r['subject_name'],$r['class_name'],$r['attendance_percent'],$r['created_at']]);
  foreach (($b['exams'] ?? []) as $r) $db->prepare('INSERT INTO exams(id,madrasa_id,exam_name,exam_type,exam_date,created_by,created_at) VALUES(?,?,?,?,?,?,?)')->execute([$r['id'],$r['madrasa_id'],$r['exam_name'],$r['exam_type'],$r['exam_date'],$r['created_by'],$r['created_at']]);
  foreach (($b['results'] ?? []) as $r) $db->prepare('INSERT INTO results(id,exam_id,student_id,marks_math,marks_science,marks_english,total_marks,grade,rank_position) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$r['id'],$r['exam_id'],$r['student_id'],$r['marks_math'],$r['marks_science'],$r['marks_english'],$r['total_marks'],$r['grade'],$r['rank_position']]);
  foreach (($b['announcements'] ?? []) as $r) $db->prepare('INSERT INTO announcements(id,madrasa_id,title,body,is_important,expiry_date,created_by,created_at) VALUES(?,?,?,?,?,?,?,?)')->execute([$r['id'],$r['madrasa_id'],$r['title'],$r['body'],$r['is_important'],$r['expiry_date'],$r['created_by'],$r['created_at']]);
  foreach (($b['attendance_records'] ?? []) as $r) $db->prepare('INSERT INTO attendance_records(id,madrasa_id,student_id,attendance_date,status) VALUES(?,?,?,?,?)')->execute([$r['id'],$r['madrasa_id'],$r['student_id'],$r['attendance_date'],$r['status']]);
  foreach (($b['messages'] ?? []) as $r) $db->prepare('INSERT INTO messages(id,madrasa_id,sender_role,sender_name,body,admin_reply,status,created_at,replied_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$r['id'],$r['madrasa_id'],$r['sender_role'],$r['sender_name'],$r['body'],$r['admin_reply'],$r['status'],$r['created_at'],$r['replied_at']]);
  foreach (($b['payrolls'] ?? []) as $r) $db->prepare('INSERT INTO payrolls(id,madrasa_id,teacher_id,staff_name,month_key,basic_amount,allowance,deduction,net_amount,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute([$r['id'],$r['madrasa_id'],$r['teacher_id'],$r['staff_name'],$r['month_key'],$r['basic_amount'],$r['allowance'],$r['deduction'],$r['net_amount'],$r['status'],$r['created_at']]);
  out(['ok'=>true]);
}

out(['error'=>'Unknown action'], 404);

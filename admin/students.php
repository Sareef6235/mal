<?php
require __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);
verify_csrf();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO students (name, class_id, age, parent_phone, notes, username, password) VALUES (:name,:class_id,:age,:parent_phone,:notes,:username,:password)');
        $stmt->execute([
            'name'=>trim($_POST['name'] ?? ''),
            'class_id'=>(int)($_POST['class_id'] ?? 0),
            'age'=>(int)($_POST['age'] ?? 0),
            'parent_phone'=>trim($_POST['parent_phone'] ?? ''),
            'notes'=>trim($_POST['notes'] ?? ''),
            'username'=>trim($_POST['username'] ?? ''),
            'password'=>password_hash($_POST['password'] ?: 'student1', PASSWORD_DEFAULT),
        ]);
        flash('ok', 'Student added');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM students WHERE id=:id');
        $stmt->execute(['id'=>(int)($_POST['id'] ?? 0)]);
        flash('ok','Student deleted');
    }
    header('Location: /admin/students.php'); exit;
}
$classes = fetch_classes($pdo);
$students = $pdo->query('SELECT s.*, c.name class_name FROM students s LEFT JOIN classes c ON c.id=s.class_id ORDER BY s.id DESC')->fetchAll();
render_header('Students');
?>
<section class="card"><h1 class="text-lg font-bold">Student Management</h1><?php if($m=flash('ok')):?><p data-autohide class="text-sm text-emerald-700"><?= e($m) ?></p><?php endif; ?>
<form method="post" class="mt-2 space-y-2"><?= csrf_input() ?><input type="hidden" name="action" value="add"><input class="input" name="name" placeholder="Name" required><select class="input" name="class_id" required><?php foreach($classes as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select><input class="input" name="age" type="number" placeholder="Age"><input class="input" name="parent_phone" placeholder="Parent phone"><input class="input" name="notes" placeholder="Notes"><input class="input" name="username" placeholder="Student username" required><input class="input" type="password" name="password" placeholder="Student password"><button class="btn btn-primary w-full">Add student</button></form></section>
<section class="mt-3 space-y-2"><?php foreach($students as $s): ?><article class="card"><div class="flex justify-between"><strong><?= e($s['name']) ?></strong><span><?= e($s['class_name']??'-') ?></span></div><p class="text-xs text-slate-500">Age <?= (int)$s['age'] ?> • <?= e($s['parent_phone']??'') ?></p><p class="text-xs"><?= e($s['notes']??'') ?></p><form method="post" class="mt-2"><?= csrf_input() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-danger">Delete</button></form></article><?php endforeach; ?></section>
<?php render_nav('profile'); render_footer(); ?>

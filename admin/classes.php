<?php
require __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);
verify_csrf();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (($_POST['action'] ?? '') === 'add') {
        $stmt = $pdo->prepare('INSERT INTO classes (name, teacher_name) VALUES (:name,:teacher_name)');
        $stmt->execute(['name'=>trim($_POST['name'] ?? ''), 'teacher_name'=>trim($_POST['teacher_name'] ?? '')]);
        flash('ok','Class added');
    }
    if (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM classes WHERE id=:id');
        $stmt->execute(['id'=>(int)($_POST['id'] ?? 0)]);
        flash('ok','Class deleted');
    }
    header('Location: /admin/classes.php'); exit;
}
$classes = fetch_classes($pdo);
render_header('Classes');
?>
<section class="card"><h1 class="text-lg font-bold">Class Management</h1><?php if($m=flash('ok')):?><p data-autohide class="text-sm text-emerald-700"><?= e($m) ?></p><?php endif; ?>
<form method="post" class="mt-2 space-y-2"><?= csrf_input() ?><input type="hidden" name="action" value="add"><input class="input" name="name" placeholder="Class name" required><input class="input" name="teacher_name" placeholder="Teacher"><button class="btn btn-primary w-full">Add class</button></form></section>
<section class="mt-3 space-y-2"><?php foreach($classes as $c): ?><article class="card flex items-center justify-between"><div><strong><?= e($c['name']) ?></strong><p class="text-xs text-slate-500">Teacher: <?= e($c['teacher_name'] ?? '-') ?></p></div><form method="post"><?= csrf_input() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-danger">Delete</button></form></article><?php endforeach; ?></section>
<?php render_nav('profile'); render_footer(); ?>

<?php
require __DIR__ . '/config/bootstrap.php';
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $subah = isset($_POST['subah']) ? 1 : 0;
    $dhuhr = isset($_POST['dhuhr']) ? 1 : 0;
    $asr = isset($_POST['asr']) ? 1 : 0;
    $maghrib = isset($_POST['maghrib']) ? 1 : 0;
    $isha = isset($_POST['isha']) ? 1 : 0;
    $points = $subah + $dhuhr + $asr + $maghrib + $isha;

    if ($studentId > 0) {
        $stmt = $pdo->prepare('INSERT INTO prayers (student_id, date, subah, dhuhr, asr, maghrib, isha, points) VALUES (:student_id,:date,:subah,:dhuhr,:asr,:maghrib,:isha,:points) ON DUPLICATE KEY UPDATE subah=VALUES(subah),dhuhr=VALUES(dhuhr),asr=VALUES(asr),maghrib=VALUES(maghrib),isha=VALUES(isha),points=VALUES(points)');
        $stmt->execute(compact('studentId', 'date', 'subah', 'dhuhr', 'asr', 'maghrib', 'isha', 'points') + ['student_id' => $studentId]);
        flash('ok', 'Prayer saved successfully');
    }
    header('Location: /tracker.php');
    exit;
}

$classes = fetch_classes($pdo);
$students = $pdo->query('SELECT s.id,s.name,c.name class_name FROM students s LEFT JOIN classes c ON c.id=s.class_id ORDER BY s.name')->fetchAll();
render_header('ട്രാക്കർ');
?>
<section class="card">
  <h1 class="text-lg font-bold">📿 നമസ്കാരം രേഖപ്പെടുത്തുക</h1>
  <?php if ($msg = flash('ok')): ?><p data-autohide class="mt-2 rounded bg-emerald-100 px-2 py-1 text-sm text-emerald-700"><?= e($msg) ?></p><?php endif; ?>
  <form method="post" class="mt-3 space-y-3">
    <?= csrf_input() ?>
    <div>
      <label class="text-sm">ക്ലാസ് തിരഞ്ഞെടുക്കുക</label>
      <select id="classFilter" class="input">
        <option value="">All</option>
        <?php foreach ($classes as $class): ?>
          <option value="<?= (int)$class['id'] ?>"><?= e($class['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm">Student Name</label>
      <select name="student_id" id="studentSelect" class="input" required>
        <option value="">Select student</option>
        <?php foreach ($students as $student): ?>
          <option data-class-id="<?= (int)$student['class_id'] ?>" value="<?= (int)$student['id'] ?>"><?= e($student['name']) ?> (<?= e($student['class_name'] ?? '-') ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <input type="date" class="input" name="date" value="<?= date('Y-m-d') ?>">
    <div class="grid grid-cols-2 gap-2 text-sm">
      <?php foreach (['subah'=>'☀️ സുബഹ്','dhuhr'=>'🌞 ളുഹ്ർ','asr'=>'⛅ അസർ','maghrib'=>'🌇 മഗരിബ്','isha'=>'🌙 ഇഷാ'] as $k=>$label): ?>
      <label class="rounded-xl border border-slate-300 bg-white px-3 py-2 flex items-center gap-2"><input type="checkbox" name="<?= $k ?>"> <?= $label ?></label>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary w-full">Save</button>
  </form>
</section>
<script>
document.getElementById('classFilter').addEventListener('change', function(){
  const v=this.value;document.querySelectorAll('#studentSelect option[data-class-id]').forEach(o=>{o.hidden=v && o.dataset.classId!==v;});
});
</script>
<?php render_nav('tracker'); render_footer(); ?>

<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$db = db_connect($config['db']);
if (!$db instanceof PDO) die('Database connection failed');
$className = trim((string) ($_GET['class_name'] ?? ''));
$students = fetch_students_for_id_cards($db, $className, 1000);
$chunks = array_chunk($students, 8);
render_header('Printable A4 ID Cards');
?>
<style>
.toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:12px 0}.sheet{width:210mm;min-height:297mm;background:#fff;margin:0 auto 12px;padding:8mm;box-shadow:0 6px 20px rgba(0,0,0,.15);color:#0f172a}
.grid-8{display:grid;grid-template-columns:repeat(2,1fr);gap:8mm}.id-card{border:1px solid #cbd5e1;border-radius:10px;overflow:hidden;min-height:62mm}
.id-head{background:#1e40af;color:#fff;text-align:center;padding:6px;font-size:13px;font-weight:700}.id-body{padding:8px;font-size:12px}.row{margin:4px 0}
.id-photo{width:72px;height:84px;border:1px solid #ddd;object-fit:cover;float:right}.id-foot{font-size:10px;text-align:center;background:#f8fafc;padding:6px;clear:both}
@media print{.hero,.toolbar{display:none}.sheet{box-shadow:none;margin:0;page-break-after:always}}
</style>
<form class="toolbar" method="get"><input type="text" name="class_name" placeholder="Class name (optional)" value="<?= e($className) ?>"><button type="submit">Generate 1000 Cards</button><button type="button" onclick="window.print()">Print A4</button></form>
<?php if (!$chunks): ?><section class="card"><p>No students found.</p></section><?php endif; ?>
<?php foreach ($chunks as $chunk): ?><div class="sheet"><div class="grid-8"><?php foreach ($chunk as $student): $classRank=rank_for_student($db,(int)$student['id'],(string)$student['class_name']); $overallRank=rank_for_student($db,(int)$student['id'],null); $photo='photos/' . preg_replace('/[^a-zA-Z0-9_\-]/','',(string)$student['register_no']) . '.jpg'; ?>
<div class="id-card"><div class="id-head">NIM MADRASA STUDENT ID</div><div class="id-body"><img class="id-photo" src="<?= e($photo) ?>" alt="Photo"><div class="row"><b>Name:</b> <?= e((string)$student['name']) ?></div><div class="row"><b>Reg No:</b> <?= e((string)$student['register_no']) ?></div><div class="row"><b>Class:</b> <?= e((string)$student['class_name']) ?></div><div class="row"><b>Year:</b> <?= e(date('Y')) ?></div><div class="row"><b>Class Rank:</b> <?= e((string)($classRank ?? '-')) ?></div><div class="row"><b>Overall Rank:</b> <?= e((string)($overallRank ?? '-')) ?></div></div><div class="id-foot">Official Student Identity Card</div></div>
<?php endforeach; ?></div></div><?php endforeach; ?>
<?php render_footer();
